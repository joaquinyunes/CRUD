<?php

namespace Tests\Feature;

use App\Models\MetodoPago;
use App\Models\OrdenCompra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReposicionTest extends TestCase
{
    use RefreshDatabase;

    public function test_sugiere_reposicion_y_genera_oc(): void
    {
        $admin = User::factory()->create(['role_id' => Role::create(['nombre' => 'Administrador'])->id]);
        $prov = Proveedor::create(['nombre' => 'Distribuidora Sur', 'cuit' => '30111111118']);
        $efectivo = MetodoPago::create(['nombre' => 'Efectivo', 'codigo' => 'efectivo', 'activo' => true, 'permite_vuelto' => true, 'orden' => 1]);

        $producto = Producto::factory()->create([
            'stock' => 5, 'precio_venta' => 100, 'precio_compra' => 60,
            'proveedor_id' => $prov->id, 'punto_pedido' => 10,
        ]);

        // Vende 30 en la ventana => ~1/día => con stock 5 la cobertura es baja.
        $this->actingAs($admin)->post(route('caja.abrir'), ['monto_inicial' => 0]);
        $this->actingAs($admin)->postJson(route('pos.store'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 3, 'precio' => 100]],
            'pagos' => [['metodo_pago_id' => $efectivo->id, 'monto' => 363]],
            'recibido' => 363,
        ])->assertOk();

        $resp = $this->actingAs($admin)->get(route('reposicion.index', ['dias' => 30, 'cobertura' => 30]));
        $resp->assertOk()->assertSee('Distribuidora Sur')->assertSee($producto->nombre);

        $this->actingAs($admin)->post(route('reposicion.generar'), [
            'proveedor_id' => $prov->id,
            'lineas' => [['producto_id' => $producto->id, 'cantidad' => 20]],
        ])->assertRedirect(route('ordenes-compra.index'));

        $oc = OrdenCompra::with('detalles')->first();
        $this->assertEquals($prov->id, $oc->proveedor_id);
        $this->assertEquals(20, $oc->detalles->first()->cantidad);
        $this->assertEquals(1200, $oc->total); // 20 * 60
    }
}
