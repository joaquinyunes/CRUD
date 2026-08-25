<?php

namespace Tests\Feature;

use App\Models\Compra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompraStockTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role_id' => Role::create(['nombre' => 'Administrador'])->id]);
    }

    private function payload(Producto $producto, array $overrides = []): array
    {
        return array_merge([
            'proveedor_id' => Proveedor::create(['nombre' => 'Prov SA', 'cuit' => '30111111118'])->id,
            'fecha'        => now()->toDateString(),
            'estado'       => 'completada',
            'detalles'     => [['producto_id' => $producto->id, 'cantidad' => 10, 'precio' => 90]],
        ], $overrides);
    }

    public function test_compra_completada_suma_stock_y_actualiza_costo(): void
    {
        $producto = Producto::factory()->create(['stock' => 5, 'precio_compra' => 80]);

        $this->actingAs($this->admin())
            ->post(route('compras.store'), $this->payload($producto))
            ->assertRedirect(route('compras.index'));

        $producto->refresh();
        $this->assertSame(15, $producto->stock);
        $this->assertEquals(90, $producto->precio_compra);
    }

    public function test_anular_compra_descuenta_stock(): void
    {
        $producto = Producto::factory()->create(['stock' => 5]);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('compras.store'), $this->payload($producto));
        $compra = Compra::first();
        $this->assertSame(15, $producto->fresh()->stock);

        $this->actingAs($admin)->delete(route('compras.destroy', $compra));

        $this->assertSame(5, $producto->fresh()->stock);
        $this->assertSame('anulada', $compra->fresh()->estado);
    }
}
