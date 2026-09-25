<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La ganancia del mes es el margen bruto sobre lo vendido, no la resta entre
 * ventas y compras: comprar mercaderia para reponer stock no es una perdida.
 */
class GananciaDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_reponer_stock_no_convierte_el_mes_en_perdida(): void
    {
        $rol = Role::create(['nombre' => 'Administrador']);
        $admin = User::factory()->create(['role_id' => $rol->id]);

        $producto = Producto::factory()->create([
            'stock'         => 500,
            'precio_compra' => 100,
            'precio_venta'  => 150,
        ]);
        $cliente = Cliente::factory()->create();

        // Vende 10 unidades: factura 1500, costo 1000, margen bruto 500.
        $this->actingAs($admin)->post(route('ventas.store'), [
            'cliente_id' => $cliente->id,
            'fecha'      => now()->toDateString(),
            'estado'     => 'completada',
            'detalles'   => [['producto_id' => $producto->id, 'cantidad' => 10, 'precio' => 150]],
        ]);

        // Y en el mismo mes repone stock por 40.000: eso no es una perdida.
        Compra::create([
            'proveedor_id' => Proveedor::create(['nombre' => 'P', 'cuit' => '30111111118'])->id,
            'numero'       => 'CMP-00001',
            'fecha'        => now()->toDateString(),
            'estado'       => 'completada',
            'subtotal'     => 40000,
            'total'        => 40000,
            'user_id'      => $admin->id,
        ]);

        $respuesta = $this->actingAs($admin)->get(route('dashboard'));

        $respuesta->assertOk();
        $this->assertSame(500.0, $respuesta->viewData('gananciaMes'));
        $this->assertGreaterThan(0, $respuesta->viewData('margenMes'));
    }
}
