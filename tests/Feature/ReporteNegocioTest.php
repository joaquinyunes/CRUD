<?php

namespace Tests\Feature;

use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReporteNegocioTest extends TestCase
{
    use RefreshDatabase;

    public function test_reporte_negocio_renderiza_con_datos(): void
    {
        $admin = User::factory()->create(['role_id' => Role::create(['nombre' => 'Administrador'])->id]);
        $efectivo = MetodoPago::create(['nombre' => 'Efectivo', 'codigo' => 'efectivo', 'activo' => true, 'permite_vuelto' => true, 'orden' => 1]);
        $producto = Producto::factory()->create(['stock' => 50, 'precio_venta' => 100, 'precio_compra' => 60]);
        Producto::factory()->create(['stock' => 10, 'nombre' => 'Sin rotación']);

        $this->actingAs($admin)->post(route('caja.abrir'), ['monto_inicial' => 0]);
        $this->actingAs($admin)->postJson(route('pos.store'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 4, 'precio' => 100]],
            'pagos' => [['metodo_pago_id' => $efectivo->id, 'monto' => 484]],
            'recibido' => 484,
        ])->assertOk();

        $resp = $this->actingAs($admin)->get(route('reportes.negocio'));
        $resp->assertOk();
        $resp->assertSee('Ticket promedio');
        $resp->assertSee('Sin rotación');
    }
}
