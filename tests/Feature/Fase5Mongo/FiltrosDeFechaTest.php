<?php

namespace Tests\Feature\Fase5Mongo;

use App\Models\Cliente;
use App\Models\MovimientoStock;
use App\Models\User;
use App\Models\Venta;
use Tests\TestCase;

/**
 * Los filtros de los listados mandan las fechas como texto ("2026-10-01"); Mongo no
 * compara un texto con una fecha, así que la consulta las convierte.
 */
class FiltrosDeFechaTest extends TestCase
{
    private function venta(string $numero, string $fecha): Venta
    {
        return Venta::create(['numero' => $numero, 'cliente_id' => 1, 'fecha' => $fecha, 'total' => 1, 'estado' => 'completada']);
    }

    public function test_los_scopes_de_fecha_aceptan_texto_e_incluyen_los_dos_extremos(): void
    {
        $this->venta('A', '2026-09-30');
        $dentro1 = $this->venta('B', '2026-10-01');
        $dentro2 = $this->venta('C', '2026-10-31');
        $this->venta('D', '2026-11-01');

        $this->assertEqualsCanonicalizing(
            [$dentro1->id, $dentro2->id],
            Venta::paraFecha('2026-10-01', '2026-10-31')->get()->modelKeys()
        );
        $this->assertSame(3, Venta::paraFecha('2026-10-01', null)->count());
        $this->assertSame(2, Venta::paraFecha(null, '2026-10-01')->count());
    }

    public function test_el_hasta_de_los_movimientos_incluye_todo_el_dia(): void
    {
        $dia = now()->subDays(3);
        foreach ([$dia->copy()->setTime(0, 5), $dia->copy()->setTime(23, 50), $dia->copy()->addDay()->setTime(0, 5)] as $momento) {
            $movimiento = MovimientoStock::create(['producto_id' => 1, 'tipo' => 'salida', 'cantidad' => 1, 'user_id' => 1]);
            $movimiento->forceFill(['created_at' => $momento])->saveQuietly();
        }

        $this->assertSame(2, MovimientoStock::paraFecha($dia->toDateString(), $dia->toDateString())->count());
    }

    public function test_el_listado_de_ventas_filtra_por_fecha_y_por_cliente(): void
    {
        $admin = User::factory()->create(['role_id' => $this->rolAdministrador()->id]);
        $ana = Cliente::factory()->create(['nombre' => 'Ana', 'apellido' => 'Pérez']);
        Venta::create(['numero' => 'VTA-1', 'cliente_id' => $ana->id, 'fecha' => '2026-10-10', 'total' => 1, 'estado' => 'completada']);
        Venta::create(['numero' => 'VTA-2', 'cliente_id' => $ana->id, 'fecha' => '2026-08-10', 'total' => 1, 'estado' => 'completada']);

        $html = $this->actingAs($admin)
            ->get(route('ventas.index', ['fecha_desde' => '2026-10-01', 'fecha_hasta' => '2026-10-31']))
            ->assertOk()->getContent();

        $this->assertStringContainsString('VTA-1', $html);
        $this->assertStringNotContainsString('VTA-2', $html);

        $this->actingAs($admin)->get(route('ventas.index', ['buscar' => 'Pérez']))->assertOk()->assertSee('VTA-2');
    }
}
