<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Role;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DevolucionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role_id' => Role::create(['nombre' => 'Administrador'])->id]);
    }

    private function crearVenta(User $admin, Producto $producto, Cliente $cliente): Venta
    {
        $this->actingAs($admin)->post(route('ventas.store'), [
            'cliente_id' => $cliente->id,
            'fecha'      => now()->toDateString(),
            'estado'     => 'completada',
            'detalles'   => [['producto_id' => $producto->id, 'cantidad' => 10, 'precio' => 100]],
        ]);

        return Venta::first();
    }

    public function test_devolucion_de_venta_reingresa_stock_y_reduce_saldo(): void
    {
        $admin = $this->admin();
        $cliente = Cliente::factory()->create(['limite_credito' => 100000]);
        $producto = Producto::factory()->create(['stock' => 20]);

        $venta = $this->crearVenta($admin, $producto, $cliente);
        $this->assertSame(10, $producto->fresh()->stock); // 20 - 10 vendidos

        $this->actingAs($admin)->post(route('devoluciones.venta.store', $venta), [
            'motivo'   => 'fallado',
            'detalles' => [['producto_id' => $producto->id, 'cantidad' => 4]],
        ])->assertRedirect(route('ventas.show', $venta));

        $this->assertSame(14, $producto->fresh()->stock);          // reingreso de 4
        $this->assertEquals(400, $venta->fresh()->totalDevuelto()); // 4 x 100
        // saldo venta = 1210 total - 0 pagado - 400 devuelto = 810
        $this->assertEquals(810, $venta->fresh()->saldoPendiente());
    }

    public function test_no_se_puede_devolver_mas_de_lo_vendido(): void
    {
        $admin = $this->admin();
        $cliente = Cliente::factory()->create(['limite_credito' => 100000]);
        $producto = Producto::factory()->create(['stock' => 20]);
        $venta = $this->crearVenta($admin, $producto, $cliente);

        $this->actingAs($admin)->post(route('devoluciones.venta.store', $venta), [
            'detalles' => [['producto_id' => $producto->id, 'cantidad' => 15]],
        ])->assertSessionHasErrors('detalles');

        $this->assertSame(10, $producto->fresh()->stock);
        $this->assertDatabaseCount('devoluciones', 0);
    }
}
