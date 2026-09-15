<?php

namespace App\Console\Commands;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Proveedor;
use App\Models\UnidadMedida;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Fase 2 (migracion a MongoDB) — copia catalogo/clientes/proveedores de la
 * base SQL a Mongo, y reescribe las FK (productos, promociones, ventas,
 * compras, presupuestos, ordenes_compra) del id numerico viejo al ObjectId
 * nuevo.
 *
 * Idempotente: usa firstOrCreate por una clave natural en Mongo, y solo
 * reescribe filas cuyo *_id todavia sea numerico (legado) — correrlo de
 * nuevo no hace nada. Excepcion: clientes sin `documento` cargado se matchean
 * por nombre+apellido, que no es una clave real — si hay dos clientes
 * homonimos sin documento y se corre el comando dos veces, se duplican.
 *
 * Requiere MONGODB_URI configurado (.env) y corrida previa de
 * `php artisan migrate` (para que existan las columnas string en SQL).
 */
class MigrarCatalogoAMongo extends Command
{
    protected $signature = 'catalogo:migrar-a-mongo {--dry-run : No escribe nada, solo muestra que haria}';

    protected $description = 'Copia categorias, unidades_medida, clientes y proveedores (SQL) a MongoDB y reescribe las FK dependientes.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->migrarColeccion(
            tablaSql: 'categorias',
            modeloMongo: Categoria::class,
            columnasFk: [
                ['tabla' => 'productos', 'columna' => 'categoria_id'],
                ['tabla' => 'promociones', 'columna' => 'categoria_id'],
            ],
            dryRun: $dryRun,
        );

        $this->migrarColeccion(
            tablaSql: 'unidades_medida',
            modeloMongo: UnidadMedida::class,
            columnasFk: [
                ['tabla' => 'productos', 'columna' => 'unidad_medida_id'],
            ],
            dryRun: $dryRun,
        );

        $this->migrarColeccion(
            tablaSql: 'clientes',
            modeloMongo: Cliente::class,
            columnasFk: [
                ['tabla' => 'ventas', 'columna' => 'cliente_id'],
                ['tabla' => 'presupuestos', 'columna' => 'cliente_id'],
            ],
            dryRun: $dryRun,
            claveMatch: fn (array $a) => empty($a['documento'])
                ? ['nombre' => $a['nombre'], 'apellido' => $a['apellido'] ?? null]
                : ['documento' => $a['documento']],
        );

        $this->migrarColeccion(
            tablaSql: 'proveedores',
            modeloMongo: Proveedor::class,
            columnasFk: [
                ['tabla' => 'compras', 'columna' => 'proveedor_id'],
                ['tabla' => 'ordenes_compra', 'columna' => 'proveedor_id'],
                ['tabla' => 'productos', 'columna' => 'proveedor_id'],
            ],
            dryRun: $dryRun,
            claveMatch: fn (array $a) => empty($a['cuit']) ? ['nombre' => $a['nombre']] : ['cuit' => $a['cuit']],
        );

        $this->info('Listo.');

        return self::SUCCESS;
    }

    /**
     * @param array<int, array{tabla: string, columna: string}> $columnasFk
     * @param (callable(array<string, mixed>): array<string, mixed>)|null $claveMatch
     *        Devuelve los campos que identifican al registro en Mongo para
     *        `firstOrCreate` (default: por `nombre`).
     */
    private function migrarColeccion(
        string $tablaSql,
        string $modeloMongo,
        array $columnasFk,
        bool $dryRun,
        ?callable $claveMatch = null,
    ): void {
        $claveMatch ??= fn (array $a) => ['nombre' => $a['nombre']];

        $filas = DB::table($tablaSql)->get();

        if ($filas->isEmpty()) {
            $this->line("{$tablaSql}: nada para migrar.");

            return;
        }

        $mapaIds = []; // id numerico viejo => ObjectId string nuevo

        foreach ($filas as $fila) {
            $atributos = (array) $fila;
            $idViejo = $atributos['id'];
            unset($atributos['id']);

            if ($dryRun) {
                $this->line("  [dry-run] {$tablaSql}#{$idViejo} -> Mongo ({$atributos['nombre']})");
                continue;
            }

            /** @var \MongoDB\Laravel\Eloquent\Model $doc */
            $doc = $modeloMongo::firstOrCreate($claveMatch($atributos), $atributos);
            $mapaIds[$idViejo] = (string) $doc->getKey();
        }

        $this->info("{$tablaSql}: ".count($mapaIds)." documento(s) en Mongo.");

        if ($dryRun) {
            return;
        }

        foreach ($columnasFk as $fk) {
            $actualizadas = 0;
            foreach ($mapaIds as $idViejo => $idNuevo) {
                $actualizadas += DB::table($fk['tabla'])
                    ->where($fk['columna'], (string) $idViejo)
                    ->update([$fk['columna'] => $idNuevo]);
            }
            $this->line("  {$fk['tabla']}.{$fk['columna']}: {$actualizadas} fila(s) actualizada(s).");
        }
    }
}
