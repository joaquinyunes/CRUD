<?php

namespace Tests\Feature;

use App\Models\Compra;
use App\Models\OrdenCompra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrdenCompraTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role_id' => Role::create(['nombre' => 'Administrador'])->id]);
    }

    private function crearOrden(User $admin, Producto $producto): OrdenCompra
    {
        $prov = Proveedor::create(['nombre' => 'Prov SA', 'cuit' => '30111111118']);
        $this->actingAs($admin)->post(route('ordenes-compra.store'), [
            'proveedor_id' => $prov->id,
            'fecha'        => now()->toDateString(),
            'detalles'     => [['producto_id' => $producto->id, 'cantidad' => 10, 'precio' => 90]],
        ]);

        return OrdenCompra::first();
    }

    public function test_recepcion_parcial_suma_stock_y_marca_parcial(): void
    {
        $admin = $this->admin();
        $producto = Producto::factory()->create(['stock' => 5]);
        $orden = $this->crearOrden($admin, $producto);
        $detalle = $orden->detalles->first();

        $this->actingAs($admin)->post(route('ordenes-compra.recibir', $orden), [
            'recibido' => [$detalle->id => 4],
        ])->assertRedirect(route('ordenes-compra.show', $orden));

        $this->assertSame(9, $producto->fresh()->stock);
        $this->assertSame('parcial', $orden->fresh()->estado);
        $this->assertSame(4, $detalle->fresh()->cantidad_recibida);
    }

    public function test_recepcion_total_marca_recibida_y_facturar_genera_compra(): void
    {
        $admin = $this->admin();
        $producto = Producto::factory()->create(['stock' => 0, 'precio_compra' => 50]);
        $orden = $this->crearOrden($admin, $producto);
        $detalle = $orden->detalles->first();

        $this->actingAs($admin)->post(route('ordenes-compra.recibir', $orden), ['recibido' => [$detalle->id => 10]]);
        $this->assertSame(10, $producto->fresh()->stock);
        $this->assertSame('recibida', $orden->fresh()->estado);

        $this->actingAs($admin)->post(route('ordenes-compra.facturar', $orden))
            ->assertRedirect(route('compras.show', Compra::first()));

        $compra = Compra::first();
        $this->assertSame(10, $producto->fresh()->stock); // facturar NO vuelve a mover stock
        $this->assertTrue((bool) $compra->stock_aplicado);
        $this->assertEquals(90, $producto->fresh()->precio_compra);
        $this->assertSame($compra->id, $orden->fresh()->compra_id);
    }

    public function test_no_se_recibe_mas_que_lo_pendiente(): void
    {
        $admin = $this->admin();
        $producto = Producto::factory()->create(['stock' => 0]);
        $orden = $this->crearOrden($admin, $producto);
        $detalle = $orden->detalles->first();

        $this->actingAs($admin)->post(route('ordenes-compra.recibir', $orden), ['recibido' => [$detalle->id => 999]]);

        $this->assertSame(10, $producto->fresh()->stock); // se topó en 10
        $this->assertSame('recibida', $orden->fresh()->estado);
    }
}
