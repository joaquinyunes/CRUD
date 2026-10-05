<?php

namespace App\Services;

use App\Exceptions\ReglaDeNegocioException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Respaldo y restauración de la base, en PHP y sin binarios externos.
 *
 * El módulo anterior ejecutaba `mysqldump` y `mysql` por línea de comandos, con
 * la conexión "mysql" escrita a mano. En la instalación que se despliega
 * (PostgreSQL, dentro de un contenedor que no trae ninguno de los dos clientes)
 * la pantalla no podía hacer nada, y la contraseña viajaba en la línea de
 * comandos, visible para cualquier proceso del servidor.
 *
 * El archivo es un .json.gz con una línea de cabecera y una línea por fila. Se
 * escribe y se lee de a una fila, así que el tamaño de la base no es el tamaño
 * de la memoria. Funciona igual en SQLite, MySQL y PostgreSQL, y solo se puede
 * restaurar en el motor y con la estructura (migraciones) con que se creó.
 */
class BackupService
{
    private const FORMATO = 1;

    private const DIRECTORIO = 'backups';

    /** Tablas que no son datos del negocio: se regeneran solas o no deben volver. */
    private const EXCLUIDAS = [
        'migrations', 'cache', 'cache_locks', 'sessions', 'jobs', 'job_batches',
        'failed_jobs', 'password_reset_tokens', 'personal_access_tokens',
    ];

    public const PATRON_NOMBRE = '/^backup_\d{4}-\d{2}-\d{2}_\d{6}\.json\.gz$/';

    /** Crea un respaldo y devuelve su nombre de archivo. */
    public function crear(): string
    {
        $nombre = 'backup_'.now()->format('Y-m-d_His').'.json.gz';
        $temporal = tempnam(sys_get_temp_dir(), 'bkp');

        $gz = gzopen($temporal, 'wb6');
        gzwrite($gz, json_encode([
            'formato'     => self::FORMATO,
            'driver'      => DB::getDriverName(),
            'fecha'       => now()->toIso8601String(),
            'migraciones' => $this->migraciones(),
        ], JSON_UNESCAPED_UNICODE)."\n");

        foreach ($this->tablasEnOrden() as $tabla) {
            foreach (DB::table($tabla)->cursor() as $fila) {
                gzwrite($gz, json_encode(['t' => $tabla, 'f' => $fila], JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION)."\n");
            }
        }
        gzclose($gz);

        $flujo = fopen($temporal, 'rb');
        $this->disco()->put(self::DIRECTORIO.'/'.$nombre, $flujo);
        fclose($flujo);
        @unlink($temporal);

        return $nombre;
    }

    /**
     * Reemplaza los datos actuales por los del respaldo, todo o nada: si algo
     * falla a mitad de camino la transacción se revierte y la base queda como estaba.
     *
     * @throws ReglaDeNegocioException
     */
    public function restaurar(string $rutaArchivo): void
    {
        $gz = @gzopen($rutaArchivo, 'rb');
        if ($gz === false) {
            throw new ReglaDeNegocioException('El archivo no es un respaldo de este sistema.', 'backup_file');
        }

        try {
            $this->verificarCabecera(json_decode((string) gzgets($gz), true));

            $tablas = $this->tablasEnOrden();

            DB::transaction(function () use ($gz, $tablas) {
                foreach (array_reverse($tablas) as $tabla) {
                    DB::table($tabla)->delete();
                }

                $this->insertarFilas($gz, $tablas);

                if (DB::getDriverName() === 'pgsql') {
                    $this->realinearSecuencias($tablas);
                }
            });
        } finally {
            gzclose($gz);
        }

        // Los permisos de cada rol están cacheados, y ya no son los mismos.
        Cache::flush();
    }

    /** @return list<array{nombre:string,tamano:float,fecha:int}> */
    public function listar(): array
    {
        $backups = [];

        foreach ($this->disco()->files(self::DIRECTORIO) as $ruta) {
            $nombre = basename($ruta);
            if (preg_match(self::PATRON_NOMBRE, $nombre)) {
                $backups[] = [
                    'nombre' => $nombre,
                    'tamano' => round($this->disco()->size($ruta) / 1024, 1),
                    'fecha'  => $this->disco()->lastModified($ruta),
                ];
            }
        }

        usort($backups, fn ($a, $b) => $b['fecha'] <=> $a['fecha']);

        return $backups;
    }

    public function existe(string $nombre): bool
    {
        return $this->nombreValido($nombre) && $this->disco()->exists(self::DIRECTORIO.'/'.$nombre);
    }

    public function descargar(string $nombre): StreamedResponse
    {
        return $this->disco()->download(self::DIRECTORIO.'/'.$nombre, $nombre);
    }

    public function eliminar(string $nombre): void
    {
        if ($this->existe($nombre)) {
            $this->disco()->delete(self::DIRECTORIO.'/'.$nombre);
        }
    }

    /** Borra los respaldos más viejos, dejando los $conservar más recientes. */
    public function podar(int $conservar): int
    {
        $sobrantes = array_slice($this->listar(), max($conservar, 1));

        foreach ($sobrantes as $backup) {
            $this->eliminar($backup['nombre']);
        }

        return count($sobrantes);
    }

    public function nombreValido(string $nombre): bool
    {
        return (bool) preg_match(self::PATRON_NOMBRE, $nombre);
    }

    private function disco(): Filesystem
    {
        return Storage::disk(config('filesystems.default'));
    }

    /** @return list<string> */
    private function migraciones(): array
    {
        return DB::table('migrations')->orderBy('migration')->pluck('migration')->all();
    }

    /**
     * @param  mixed  $cabecera
     *
     * @throws ReglaDeNegocioException
     */
    private function verificarCabecera($cabecera): void
    {
        if (! is_array($cabecera) || ($cabecera['formato'] ?? null) !== self::FORMATO) {
            throw new ReglaDeNegocioException('El archivo no es un respaldo de este sistema.', 'backup_file');
        }

        if (($cabecera['driver'] ?? null) !== DB::getDriverName()) {
            throw new ReglaDeNegocioException(
                'El respaldo es de una base '.($cabecera['driver'] ?? '?').' y esta instalación usa '.DB::getDriverName().'.',
                'backup_file'
            );
        }

        if (($cabecera['migraciones'] ?? null) !== $this->migraciones()) {
            throw new ReglaDeNegocioException(
                'El respaldo es de otra versión de la estructura de la base. Actualizá el sistema a la versión con que se creó antes de restaurarlo.',
                'backup_file'
            );
        }
    }

    /**
     * @param  resource  $gz
     * @param  list<string>  $tablas
     */
    private function insertarFilas($gz, array $tablas): void
    {
        $conocidas = array_flip($tablas);
        $tabla = '';
        $lote = [];

        while (($linea = gzgets($gz)) !== false) {
            $registro = json_decode($linea, true);

            if (! is_array($registro) || ! isset($registro['t'], $registro['f']) || ! isset($conocidas[$registro['t']])) {
                throw new ReglaDeNegocioException('El respaldo está dañado o no pertenece a este sistema.', 'backup_file');
            }

            if ($registro['t'] !== $tabla || count($lote) >= 500) {
                $this->insertarLote($tabla, $lote);
                $tabla = $registro['t'];
                $lote = [];
            }

            $lote[] = $registro['f'];
        }

        $this->insertarLote($tabla, $lote);
    }

    /** @param  list<array<string,mixed>>  $filas */
    private function insertarLote(string $tabla, array $filas): void
    {
        if ($filas === []) {
            return;
        }

        // Un tope de variables por sentencia: SQLite arrastra límites bajos.
        foreach (array_chunk($filas, max(1, intdiv(900, count($filas[0])))) as $trozo) {
            DB::table($tabla)->insert($trozo);
        }
    }

    /**
     * Insertar ids a mano no avanza la secuencia de PostgreSQL: la próxima alta
     * intentaría el id 1 y chocaría con una fila restaurada.
     *
     * @param  list<string>  $tablas
     */
    private function realinearSecuencias(array $tablas): void
    {
        foreach ($tablas as $tabla) {
            if (Schema::hasColumn($tabla, 'id')) {
                DB::statement(
                    "SELECT setval(pg_get_serial_sequence('\"{$tabla}\"', 'id'), COALESCE(MAX(id), 1), MAX(id) IS NOT NULL) FROM \"{$tabla}\""
                );
            }
        }
    }

    /**
     * Las tablas de datos, con cada una después de las que referencia por clave
     * foránea: se vacía en el orden inverso y se llena en este.
     *
     * @return list<string>
     */
    private function tablasEnOrden(): array
    {
        $tablas = array_values(array_diff(Schema::getTableListing(schemaQualified: false), self::EXCLUIDAS));
        sort($tablas);

        $dependencias = [];
        foreach ($tablas as $tabla) {
            $dependencias[$tabla] = collect(Schema::getForeignKeys($tabla))
                ->pluck('foreign_table')
                ->unique()
                ->filter(fn ($destino) => $destino !== $tabla && in_array($destino, $tablas, true))
                ->values()
                ->all();
        }

        $orden = [];
        while ($dependencias !== []) {
            $listas = array_keys(array_filter(
                $dependencias,
                fn ($destinos) => array_diff($destinos, $orden) === []
            ));

            if ($listas === []) {
                // Un ciclo entre tablas: se las agrega tal cual en vez de colgarse.
                $listas = array_keys($dependencias);
            }

            foreach ($listas as $tabla) {
                $orden[] = $tabla;
                unset($dependencias[$tabla]);
            }
        }

        return $orden;
    }
}
