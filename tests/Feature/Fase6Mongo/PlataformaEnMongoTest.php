<?php

namespace Tests\Feature\Fase6Mongo;

use App\Exceptions\ReglaDeNegocioException;
use App\Models\Auditoria;
use App\Models\Cliente;
use App\Models\ModeloMongo;
use App\Models\Producto;
use App\Models\Setting;
use App\Models\User;
use App\Services\BackupService;
use App\Services\VentaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use MongoDB\BSON\Decimal128;
use MongoDB\BSON\UTCDateTime;
use Tests\TestCase;

/**
 * Fase 6: respaldo y restauración reales, auditoría y configuración sobre MongoDB.
 */
class PlataformaEnMongoTest extends TestCase
{
    private function ventaConPlata(): int
    {
        $producto = Producto::factory()->create(['stock' => 10, 'precio_compra' => 33.33]);

        return app(VentaService::class)->crear([
            'cliente_id' => Cliente::factory()->create()->id,
            'fecha'      => '2026-10-27',
            'estado'     => 'completada',
            'detalles'   => [['producto_id' => $producto->id, 'cantidad' => 3, 'precio' => 99.99]],
        ])->id;
    }

    public function test_la_restauracion_devuelve_los_mismos_tipos_ids_decimales_y_fechas(): void
    {
        Storage::fake('local');
        $ventaId = $this->ventaConPlata();
        $coleccion = DB::connection('mongodb')->getCollection('ventas');
        $antes = $coleccion->findOne(['_id' => $ventaId]);
        $contadoresAntes = DB::connection('mongodb')->getCollection('contadores')->countDocuments();

        $servicio = app(BackupService::class);
        $nombre = $servicio->crear();

        DB::connection('mongodb')->getDatabase()->drop();
        $this->assertSame(0, $coleccion->countDocuments());

        $servicio->restaurar(Storage::disk('local')->path('backups/'.$nombre));

        $despues = $coleccion->findOne(['_id' => $ventaId]);
        $this->assertIsInt($despues['_id']);
        $this->assertInstanceOf(Decimal128::class, $despues['total_final']);
        $this->assertInstanceOf(UTCDateTime::class, $despues['fecha']);
        $this->assertEquals($antes->getArrayCopy(), $despues->getArrayCopy());
        $this->assertSame($contadoresAntes, DB::connection('mongodb')->getCollection('contadores')->countDocuments());

        // La numeración y los ids siguen donde estaban: la próxima venta no repite nada.
        $siguiente = app(VentaService::class)->crear([
            'cliente_id' => Cliente::first()->id,
            'fecha'      => '2026-10-28',
            'estado'     => 'completada',
            'detalles'   => [['producto_id' => Producto::first()->id, 'cantidad' => 1, 'precio' => 10]],
        ]);
        $this->assertSame('VTA-00002', $siguiente->numero);
        $this->assertSame($ventaId + 1, $siguiente->id);
    }

    public function test_toda_coleccion_de_un_modelo_esta_declarada_y_entra_en_el_respaldo(): void
    {
        $declaradas = array_keys(require database_path('mongo/indices.php'));
        $sinTabla = ['personal_access_tokens']; // los tokens no se respaldan a propósito

        $faltan = [];
        foreach (glob(app_path('Models/*.php')) as $archivo) {
            $clase = 'App\\Models\\'.basename($archivo, '.php');
            $reflexion = new \ReflectionClass($clase);

            if ($reflexion->isAbstract() || ! $reflexion->isSubclassOf(ModeloMongo::class)) {
                continue;
            }

            $coleccion = (new $clase)->getTable();
            if (! in_array($coleccion, $declaradas, true) && ! in_array($coleccion, $sinTabla, true)) {
                $faltan[] = $coleccion;
            }
        }

        $this->assertSame([], $faltan, 'Colecciones sin declarar en database/mongo/indices.php: no entrarían en el respaldo.');
        $this->assertContains('stock_deposito', $declaradas);
        $this->assertContains('contadores', $declaradas);
    }

    public function test_no_se_restaura_un_respaldo_de_una_base_sql(): void
    {
        $viejo = tempnam(sys_get_temp_dir(), 'x');
        file_put_contents($viejo, gzencode(json_encode(['formato' => 1, 'driver' => 'pgsql', 'migraciones' => []])."\n"));

        $this->expectException(ReglaDeNegocioException::class);
        $this->expectExceptionMessage('base pgsql');
        app(BackupService::class)->restaurar($viejo);
    }

    public function test_el_respaldo_no_incluye_tokens_de_api(): void
    {
        Storage::fake('local');
        User::factory()->create()->createToken('movil');

        $nombre = app(BackupService::class)->crear();

        $this->assertStringNotContainsString('personal_access_tokens', gzdecode(Storage::disk('local')->get('backups/'.$nombre)));
    }

    public function test_la_auditoria_guarda_valores_legibles_no_tipos_de_mongo(): void
    {
        Producto::factory()->create(['nombre' => 'Taladro', 'precio_compra' => 1234.5]);

        $registro = Auditoria::where('modelo_afectado', Producto::class)->where('accion', 'created')->firstOrFail();

        $this->assertSame('Taladro', $registro->valor_nuevo['nombre']);
        $this->assertSame('1234.50', $registro->valor_nuevo['precio_compra']);
        $this->assertIsString($registro->valor_nuevo['created_at']);
    }

    public function test_la_configuracion_se_guarda_una_sola_vez_por_clave(): void
    {
        Setting::establecer('ventas_prefijo_numero', 'FAC', 'ventas');
        Setting::establecer('ventas_prefijo_numero', 'REC', 'ventas');

        $this->assertSame(1, Setting::where('clave', 'ventas_prefijo_numero')->count());
        $this->assertSame('REC', Setting::obtener('ventas_prefijo_numero'));
        $this->assertSame(['ventas_prefijo_numero' => 'REC'], Setting::obtenerGrupo('ventas'));
    }
}
