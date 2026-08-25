<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\Role;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VentaStockTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $rol = Role::create(['nombre' => 'Administrador']);

        return User::factory()->create(['role_id' => $rol->id]);
    }

    private function payloadVenta(Producto $producto, array $overrides = []): array
    {
        return array_merge([
            'cliente_id' => Cliente::factory()->create()->id,
            'fecha'      => now()->toDateString(),
            'estado'     => 'completada',
            'detalles'   => [
                ['producto_id' => $producto->id, 'cantidad' => 3, 'precio' => 150],
            ],
        ], $overrides);
    }

    public function test_crear_venta_completada_descuenta_stock(): void
    {
        $producto = Producto::factory()->create(['stock' => 10]);

        $this->actingAs($this->admin())
            ->post(route('ventas.store'), $this->payloadVenta($producto))
            ->assertRedirect(route('ventas.index'));

        $this->assertSame(7, $producto->fresh()->stock);
        $this->assertDatabaseHas('movimientos_stock', [
            'producto_id' => $producto->id, 'tipo' => 'salida', 'cantidad' => 3,
        ]);
    }

    public function test_venta_pendiente_no_toca_stock(): void
    {
        $producto = Producto::factory()->create(['stock' => 10]);

        $this->actingAs($this->admin())
            ->post(route('ventas.store'), $this->payloadVenta($producto, ['estado' => 'pendiente']));

        $this->assertSame(10, $producto->fresh()->stock);
    }

    public function test_no_permite_sobrevender(): void
    {
        $producto = Producto::factory()->create(['stock' => 2]);

        $this->actingAs($this->admin())
            ->post(route('ventas.store'), $this->payloadVenta($producto))
            ->assertSessionHasErrors('detalles');

        $this->assertSame(2, $producto->fresh()->stock);
        $this->assertDatabaseCount('ventas', 0);
    }

    public function test_anular_venta_devuelve_stock(): void
    {
        $producto = Producto::factory()->create(['stock' => 10]);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('ventas.store'), $this->payloadVenta($producto));
        $venta = Venta::first();
        $this->assertSame(7, $producto->fresh()->stock);

        $this->actingAs($admin)->delete(route('ventas.destroy', $venta), ['motivo' => 'error de carga']);

        $this->assertSame(10, $producto->fresh()->stock);
        $this->assertSame('anulada', $venta->fresh()->estado);
    }

    public function test_editar_venta_completada_reajusta_stock(): void
    {
        $producto = Producto::factory()->create(['stock' => 20]);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('ventas.store'), $this->payloadVenta($producto));
        $venta = Venta::first();
        $this->assertSame(17, $producto->fresh()->stock);

        $this->actingAs($admin)->put(route('ventas.update', $venta), $this->payloadVenta($producto, [
            'cliente_id' => $venta->cliente_id,
            'detalles'   => [['producto_id' => $producto->id, 'cantidad' => 8, 'precio' => 150]],
        ]));

        $this->assertSame(12, $producto->fresh()->stock);
    }

    public function test_totales_con_descuento_porcentaje_e_iva(): void
    {
        $producto = Producto::factory()->create(['stock' => 100, 'precio_compra' => 80]);

        $this->actingAs($this->admin())->post(route('ventas.store'), $this->payloadVenta($producto, [
            'detalles'       => [['producto_id' => $producto->id, 'cantidad' => 2, 'precio' => 100]],
            'descuento'      => 10,
            'descuento_tipo' => 'porcentaje',
        ]));

        $venta = Venta::first();
        // subtotal 200 - 10% (20) = 180 base ; IVA 21% = 37.80 ; total 217.80
        $this->assertEquals(200, $venta->subtotal);
        $this->assertEquals(20, $venta->descuento);
        $this->assertEquals(37.80, $venta->impuesto);
        $this->assertEquals(217.80, $venta->total_final);
        $this->assertEquals(80, $venta->detalles->first()->costo_unitario);
    }

    public function test_conciliacion_de_pagos_marca_estado_pago(): void
    {
        $producto = Producto::factory()->create(['stock' => 100]);
        $metodo = MetodoPago::create(['nombre' => 'Efectivo', 'codigo' => 'efectivo', 'activo' => true, 'orden' => 1]);

        $this->actingAs($this->admin())->post(route('ventas.store'), $this->payloadVenta($producto, [
            'detalles'     => [['producto_id' => $producto->id, 'cantidad' => 1, 'precio' => 100]],
            'metodos_pago' => [['metodo_pago_id' => $metodo->id, 'monto' => 50]],
        ]));

        $venta = Venta::first();
        $this->assertEquals(50, $venta->pagado);
        $this->assertSame('parcial', $venta->estado_pago);
    }
}
