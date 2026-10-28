<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportePeriodoTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role_id' => $this->rolAdministrador()->id]);
    }

    public function test_reportes_por_periodo_se_muestran(): void
    {
        $admin = $this->admin();
        $producto = Producto::factory()->create(['stock' => 100]);
        $cliente = Cliente::factory()->create();

        foreach ([0, 9, 40, 200] as $diasAtras) {
            $this->actingAs($admin)->post(route('ventas.store'), [
                'cliente_id' => $cliente->id,
                'fecha'      => now()->subDays($diasAtras)->toDateString(),
                'estado'     => 'completada',
                'detalles'   => [['producto_id' => $producto->id, 'cantidad' => 1, 'precio' => 100]],
            ]);
        }

        $this->assertSame(4, Venta::where('estado', 'completada')->count());

        foreach (['diario', 'semanal', 'mensual'] as $periodo) {
            $this->actingAs($admin)
                ->get(route('reportes.ventas-periodo', ['periodo' => $periodo]))
                ->assertOk();

            $this->actingAs($admin)
                ->get(route('reportes.compras-periodo', ['periodo' => $periodo]))
                ->assertOk();
        }
    }

    public function test_dashboard_agrupa_ventas_por_dia(): void
    {
        $admin = $this->admin();
        Producto::factory()->create(['stock' => 10]);

        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
    }
}
