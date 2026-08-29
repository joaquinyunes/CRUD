<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Deposito;
use App\Models\Producto;
use App\Models\Role;
use App\Models\User;
use App\Models\Venta;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepositoTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role_id' => Role::create(['nombre' => 'Administrador'])->id]);
    }

    public function test_transferencia_mueve_stock_sin_cambiar_el_total(): void
    {
        $admin = $this->admin();
        $principal = Deposito::principalId();
        $sucursal = Deposito::create(['nombre' => 'Sucursal', 'activo' => true]);
        $producto = Producto::factory()->create(['stock' => 20]);

        $this->actingAs($admin)->post(route('depositos.transferir'), [
            'producto_id' => $producto->id,
            'origen_id'   => $principal,
            'destino_id'  => $sucursal->id,
            'cantidad'    => 8,
        ])->assertRedirect(route('depositos.index'));

        $producto->refresh();
        $this->assertSame(20, $producto->stock);                 // total intacto
        $this->assertSame(12, $producto->stockEn($principal));
        $this->assertSame(8, $producto->stockEn($sucursal->id));
    }

    public function test_no_se_transfiere_mas_de_lo_disponible_en_origen(): void
    {
        $admin = $this->admin();
        $sucursal = Deposito::create(['nombre' => 'Sucursal', 'activo' => true]);
        $producto = Producto::factory()->create(['stock' => 5]);

        $this->actingAs($admin)->post(route('depositos.transferir'), [
            'producto_id' => $producto->id,
            'origen_id'   => $sucursal->id, // vacío
            'destino_id'  => Deposito::principalId(),
            'cantidad'    => 3,
        ])->assertSessionHasErrors('cantidad');
    }

    public function test_venta_descuenta_del_deposito_elegido(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        $sucursal = Deposito::create(['nombre' => 'Sucursal', 'activo' => true]);
        $producto = Producto::factory()->create(['stock' => 10]);
        // muevo 6 a la sucursal
        app(StockService::class)->transferir($producto, 6, Deposito::principalId(), $sucursal->id);

        $this->actingAs($admin)->post(route('ventas.store'), [
            'cliente_id'  => Cliente::factory()->create()->id,
            'deposito_id' => $sucursal->id,
            'fecha'       => now()->toDateString(),
            'estado'      => 'completada',
            'detalles'    => [['producto_id' => $producto->id, 'cantidad' => 4, 'precio' => 100]],
        ])->assertRedirect(route('ventas.index'));

        $producto->refresh();
        $this->assertSame(6, $producto->stock);                       // 10 - 4
        $this->assertSame(2, $producto->stockEn($sucursal->id));      // 6 - 4
        $this->assertSame(4, $producto->stockEn(Deposito::principalId()));
        $this->assertSame($sucursal->id, Venta::first()->deposito_id);
    }

    public function test_no_se_puede_sobrevender_en_un_deposito_aunque_el_total_alcance(): void
    {
        $admin = $this->admin();
        $sucursal = Deposito::create(['nombre' => 'Sucursal', 'activo' => true]);
        $producto = Producto::factory()->create(['stock' => 10]); // todo en principal, sucursal en 0

        $this->actingAs($admin)->post(route('ventas.store'), [
            'cliente_id'  => Cliente::factory()->create()->id,
            'deposito_id' => $sucursal->id,
            'fecha'       => now()->toDateString(),
            'estado'      => 'completada',
            'detalles'    => [['producto_id' => $producto->id, 'cantidad' => 3, 'precio' => 100]],
        ])->assertSessionHasErrors('detalles');

        $this->assertSame(10, $producto->fresh()->stock);
        $this->assertDatabaseCount('ventas', 0);
    }
}
