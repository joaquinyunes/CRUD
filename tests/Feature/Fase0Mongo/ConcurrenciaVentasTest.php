<?php

namespace Tests\Feature\Fase0Mongo;

use App\Models\Cliente;
use App\Models\Deposito;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\LimpiaMongo;
use Tests\TestCase;

/**
 * La compuerta de la fase 0: ventas que llegan al mismo tiempo desde procesos
 * distintos (como en producción) no repiten número ni dejan stock negativo.
 *
 * Los hijos necesitan su propia base SQL (usuarios, configuración y auditoría
 * todavía no están en Mongo): un archivo SQLite temporal, migrado una vez.
 */
class ConcurrenciaVentasTest extends TestCase
{
    use LimpiaMongo, RefreshDatabase;

    private string $sqlite;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sqlite = tempnam(sys_get_temp_dir(), 'conc');
        $this->ejecutar([PHP_BINARY, 'artisan', 'migrate', '--force', '--no-interaction']);
    }

    protected function tearDown(): void
    {
        @unlink($this->sqlite);
        parent::tearDown();
    }

    /** @return array<string,string> */
    private function entorno(): array
    {
        return array_merge(getenv(), [
            'APP_ENV'          => 'testing',
            'DB_CONNECTION'    => 'sqlite',
            'DB_DATABASE'      => $this->sqlite,
            'DB_URL'           => '',
            'MONGODB_DATABASE' => config('database.connections.mongodb.database'),
            'MONGODB_URI'      => config('database.connections.mongodb.dsn'),
            'QUEUE_CONNECTION' => 'sync',
            'CACHE_STORE'      => 'array',
            'SESSION_DRIVER'   => 'array',
        ]);
    }

    /** @param  list<string>  $comando */
    private function ejecutar(array $comando): void
    {
        $proceso = proc_open($comando, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path(), $this->entorno());
        $salida = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
        $this->assertSame(0, proc_close($proceso), $salida);
    }

    /**
     * Lanza $procesos hijos que venden a la vez, $veces ventas cada uno, y
     * devuelve el resultado de todas. Cada proceso vende uno de $productoIds (en
     * orden y dando la vuelta).
     *
     * @param  list<int>  $productoIds
     * @return list<array{ok:bool,numero?:string,error?:string}>
     */
    private function venderEnParalelo(int $procesos, int $veces, array $productoIds, int $clienteId, int $cantidad): array
    {
        $largada = microtime(true) + 6.0; // da tiempo a que arranquen todos
        $hijos = [];

        for ($i = 0; $i < $procesos; $i++) {
            $proceso = proc_open(
                [PHP_BINARY, base_path('tests/Support/vender_worker.php'), (string) $productoIds[$i % count($productoIds)], (string) $clienteId, (string) $cantidad, (string) $largada, (string) $veces],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes,
                base_path(),
                $this->entorno()
            );
            $hijos[] = [$proceso, $pipes];
        }

        $respuestas = [];
        foreach ($hijos as [$proceso, $pipes]) {
            $salida = trim(stream_get_contents($pipes[1]));
            $errores = stream_get_contents($pipes[2]);
            proc_close($proceso);
            $respuestas = array_merge($respuestas, json_decode($salida, true) ?? [['ok' => false, 'error' => "sin respuesta: {$salida} {$errores}"]]);
        }

        return $respuestas;
    }

    public function test_ventas_simultaneas_no_repiten_numero(): void
    {
        // Un producto por proceso: así las ventas no chocan por el stock y lo único que
        // las puede pisar es el contador del número, que es lo que se prueba acá.
        $productos = Producto::factory()->count(6)->create(['stock' => 100]);
        $cliente = Cliente::factory()->create();

        // 6 procesos x 5 ventas seguidas: 30 ventas que se pisan entre sí.
        $respuestas = $this->venderEnParalelo(6, 5, $productos->modelKeys(), $cliente->id, 1);

        $this->assertSame([], array_values(array_filter($respuestas, fn ($r) => ! $r['ok'])), json_encode($respuestas));
        $numeros = array_column($respuestas, 'numero');
        $this->assertCount(30, array_unique($numeros), 'Números repetidos: '.implode(', ', $numeros));

        sort($numeros);
        $this->assertSame(
            array_map(fn ($n) => 'VTA-'.str_pad((string) $n, 5, '0', STR_PAD_LEFT), range(1, 30)),
            $numeros,
            'La serie tiene que ser correlativa y sin huecos.'
        );
        $this->assertSame(600 - 30, (int) Producto::sum('stock'));
        $this->assertSame(30, DB::connection('mongodb')->table('ventas')->count());
    }

    public function test_ventas_simultaneas_no_dejan_stock_negativo(): void
    {
        $producto = Producto::factory()->create(['stock' => 20]);
        $cliente = Cliente::factory()->create();

        // 30 intentos de vender 1 unidad sobre 20 de stock: salen exactamente 20.
        $respuestas = $this->venderEnParalelo(6, 5, [$producto->id], $cliente->id, 1);

        $exitosas = array_filter($respuestas, fn ($r) => $r['ok']);
        $sinStock = array_filter($respuestas, fn ($r) => ! $r['ok'] && $r['error'] === 'stock');
        $this->assertCount(20, $exitosas, json_encode($respuestas));
        $this->assertCount(10, $sinStock, json_encode($respuestas));

        $this->assertSame(0, $producto->fresh()->stock);
        $this->assertSame(0, $producto->stockEn(Deposito::principalId()));
        $this->assertSame(20, DB::connection('mongodb')->table('ventas')->count());
        $this->assertSame(20, DB::connection('mongodb')->table('movimientos_stock')->where('tipo', 'salida')->count());
        $this->assertCount(20, array_unique(array_column($exitosas, 'numero')));
    }
}
