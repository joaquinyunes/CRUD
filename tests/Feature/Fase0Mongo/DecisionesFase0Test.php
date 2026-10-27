<?php

namespace Tests\Feature\Fase0Mongo;

use App\Exceptions\StockInsuficienteException;
use App\Models\CajaSesion;
use App\Models\Cliente;
use App\Models\Deposito;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Services\VentaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use MongoDB\BSON\Decimal128;
use Tests\Concerns\LimpiaMongo;
use Tests\TestCase;

/**
 * Las decisiones D1, D2 y D6 de PLAN-CRUD-MONGODB.md, comprobadas con pruebas chicas.
 */
class DecisionesFase0Test extends TestCase
{
    use LimpiaMongo, RefreshDatabase;

    private function ventaDe(Producto $producto, int $cantidad = 1): Venta
    {
        return app(VentaService::class)->crear([
            'cliente_id' => Cliente::factory()->create()->id,
            'fecha'      => now()->toDateString(),
            'estado'     => 'completada',
            'detalles'   => [['producto_id' => $producto->id, 'cantidad' => $cantidad, 'precio' => 100]],
        ]);
    }

    // ---- D1: ids numéricos por secuencia ----

    public function test_d1_los_ids_son_enteros_consecutivos_y_se_buscan_por_ruta(): void
    {
        $producto = Producto::factory()->create(['stock' => 10]);

        $primera = $this->ventaDe($producto);
        $segunda = $this->ventaDe($producto);

        $this->assertIsInt($primera->id);
        $this->assertSame($primera->id + 1, $segunda->id);

        // El id viaja como texto en la URL y en los formularios.
        $this->assertSame($primera->id, Venta::findOrFail((string) $primera->id)->id);
        $this->assertSame($primera->id, Venta::whereIn('id', [(string) $primera->id])->first()->id);

        $crudo = DB::connection('mongodb')->getCollection('ventas')->findOne(['_id' => $primera->id]);
        $this->assertIsInt($crudo['_id']);

        $admin = User::factory()->create(['role_id' => $this->rolAdministrador()->id]);
        $this->actingAs($admin)
            ->get(route('ventas.show', $primera))
            ->assertOk()
            ->assertSee($primera->numero);
    }

    public function test_d1_una_clave_foranea_que_llega_como_texto_se_guarda_como_entero(): void
    {
        $cliente = Cliente::factory()->create();

        $venta = Venta::create([
            'numero' => 'X-1', 'cliente_id' => (string) $cliente->id, 'fecha' => now(),
            'total'  => 1, 'estado' => 'pendiente',
        ]);

        $crudo = DB::connection('mongodb')->getCollection('ventas')->findOne(['_id' => $venta->id]);
        $this->assertSame($cliente->id, $crudo['cliente_id']);
        $this->assertSame($venta->id, $cliente->ventas()->first()->id);
    }

    public function test_una_venta_que_falla_a_mitad_no_deja_nada_a_medias_ni_gasta_numero(): void
    {
        $hay = Producto::factory()->create(['stock' => 10]);
        $noHay = Producto::factory()->create(['stock' => 1]);

        try {
            app(VentaService::class)->crear([
                'cliente_id' => Cliente::factory()->create()->id,
                'fecha'      => now()->toDateString(),
                'estado'     => 'completada',
                'detalles'   => [
                    ['producto_id' => $hay->id, 'cantidad' => 4, 'precio' => 100],
                    ['producto_id' => $noHay->id, 'cantidad' => 5, 'precio' => 100],
                ],
            ]);
            $this->fail('Tendría que haber fallado por falta de stock.');
        } catch (StockInsuficienteException) {
        }

        $this->assertSame(10, $hay->fresh()->stock);
        $this->assertSame(10, $hay->stockEn(Deposito::principalId()));
        $this->assertDatabaseCount('ventas', 0, 'mongodb');
        $this->assertDatabaseCount('ventas_detalle', 0, 'mongodb');
        $this->assertDatabaseCount('movimientos_stock', 0, 'mongodb');

        // El contador se revirtió con la transacción: la serie no deja un hueco.
        $this->assertSame('VTA-00001', $this->ventaDe($hay)->numero);
    }

    // ---- D6: el dinero no pasa por float ----

    public function test_d6_el_dinero_se_guarda_como_decimal128_y_suma_exacto(): void
    {
        $sesion = CajaSesion::create(['user_id' => 1, 'monto_inicial' => 0, 'estado' => 'abierta', 'abierta_en' => now()]);

        foreach ([0.10, 0.20] as $monto) {
            $sesion->movimientos()->create(['tipo' => 'ingreso', 'concepto' => 'x', 'monto' => $monto]);
        }

        $crudo = DB::connection('mongodb')->getCollection('caja_movimientos')->findOne();
        $this->assertInstanceOf(Decimal128::class, $crudo['monto']);
        $this->assertSame('0.10', (string) $crudo['monto']);

        // En float 0.1 + 0.2 = 0.30000000000000004.
        $this->assertSame(0.30, $sesion->totalIngresos());
        $this->assertSame('0.30', number_format($sesion->saldoEsperado(), 2, '.', ''));
    }

    public function test_d6_los_totales_de_una_venta_quedan_en_decimal128(): void
    {
        $producto = Producto::factory()->create(['stock' => 10]);
        $venta = $this->ventaDe($producto, 3);

        $crudo = DB::connection('mongodb')->getCollection('ventas')->findOne(['_id' => $venta->id]);
        foreach (['subtotal', 'total', 'total_final', 'impuesto', 'pagado'] as $campo) {
            $this->assertInstanceOf(Decimal128::class, $crudo[$campo], $campo);
        }
        $this->assertSame('363.00', (string) $crudo['total_final']); // 3 x 100 + 21 %
    }

    // ---- D2: sesión por cookie, caché por archivo, cola sync (sin tablas SQL) ----

    public function test_d2_login_cache_y_cola_funcionan_sin_las_tablas_sessions_cache_y_jobs(): void
    {
        config([
            'session.driver'         => 'cookie',
            'cache.default'          => 'file',
            'queue.default'          => 'sync',
            'cache.stores.file.path' => storage_path('framework/cache/test-d2'),
        ]);

        foreach (['sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs'] as $tabla) {
            Schema::dropIfExists($tabla);
        }

        $usuario = User::factory()->create(['role_id' => $this->rolAdministrador()->id]);

        $this->post('/login', ['email' => $usuario->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();

        // La sesión viaja en la cookie: la segunda petición sigue autenticada.
        $this->get(route('caja.index'))->assertOk();

        Cache::put('d2', 'ok', 60);
        $this->assertSame('ok', Cache::get('d2'));
        Cache::flush();

        // Una venta dispara VentaCreada (listener en cola) y con `sync` corre en el acto.
        $producto = Producto::factory()->create(['stock' => 10]);
        $this->ventaDe($producto);
        $this->assertDatabaseCount('ventas', 1, 'mongodb');
    }
}
