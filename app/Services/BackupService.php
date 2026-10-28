<?php

namespace App\Services;

use App\Exceptions\ReglaDeNegocioException;
use App\Support\Mongo\Conexion;
use App\Support\Mongo\Transaccion;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use MongoDB\BSON\Document;
use MongoDB\Database;
use MongoDB\Driver\Session;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Respaldo y restauración de la base, en PHP y sin binarios externos.
 *
 * El archivo es un .json.gz con una línea de cabecera y una línea por documento:
 * `{"t":"ventas","f":{...}}`, donde `f` es JSON extendido canónico de MongoDB. Así
 * los ids enteros, los Decimal128 (el dinero) y las fechas vuelven exactamente como
 * estaban. Se escribe y se lee de a un documento, así que el tamaño de la base no
 * es el tamaño de la memoria.
 *
 * Solo se restaura en el mismo motor (el respaldo de una instalación SQL anterior
 * se rechaza) y en una versión del sistema que conozca todas sus colecciones.
 */
class BackupService
{
    private const FORMATO = 1;

    private const MOTOR = 'mongodb';

    private const DIRECTORIO = 'backups';

    /** Colecciones que no son datos del negocio: no deben volver con una restauración. */
    private const EXCLUIDAS = ['personal_access_tokens', 'password_reset_tokens'];

    private const LOTE = 500;

    public const PATRON_NOMBRE = '/^backup_\d{4}-\d{2}-\d{2}_\d{6}\.json\.gz$/';

    /** Crea un respaldo y devuelve su nombre de archivo. */
    public function crear(): string
    {
        $nombre = 'backup_'.now()->format('Y-m-d_His').'.json.gz';
        // En storage y no en el directorio temporal del sistema: en Windows y en
        // algunos contenedores ese directorio no es escribible por el proceso web.
        File::ensureDirectoryExists(storage_path('app/tmp'));
        $temporal = (string) tempnam(storage_path('app/tmp'), 'bkp');
        $colecciones = $this->colecciones();

        $gz = gzopen($temporal, 'wb6');
        gzwrite($gz, json_encode([
            'formato'     => self::FORMATO,
            'driver'      => self::MOTOR,
            'fecha'       => now()->toIso8601String(),
            'colecciones' => $colecciones,
        ], JSON_UNESCAPED_UNICODE)."\n");

        foreach ($colecciones as $coleccion) {
            $documentos = $this->base()->selectCollection($coleccion)->find([], [
                'typeMap' => ['root' => 'bson', 'document' => 'bson', 'array' => 'bson'],
            ]);

            foreach ($documentos as $documento) {
                assert($documento instanceof Document);

                gzwrite($gz, '{"t":'.json_encode($coleccion).',"f":'.$documento->toCanonicalExtendedJSON()."}\n");
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
     * shortcut: una sola transacción de MongoDB dura como máximo 60 s (por defecto);
     * alcanza para decenas de miles de documentos. Para bases más grandes, restaurar en una
     * base temporal y renombrarla.
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

            $colecciones = $this->colecciones();

            Transaccion::en(function () use ($gz, $colecciones) {
                $opciones = $this->opciones();

                foreach ($colecciones as $coleccion) {
                    $this->base()->selectCollection($coleccion)->deleteMany([], $opciones);
                }

                $this->insertarDocumentos($gz, $colecciones, $opciones);
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

    private function base(): Database
    {
        return Conexion::mongo()->getDatabase();
    }

    /** @return array{session?: Session} */
    private function opciones(): array
    {
        $sesion = Conexion::mongo()->getSession();

        return $sesion ? ['session' => $sesion] : [];
    }

    /**
     * Las colecciones de datos: las que declara database/mongo/indices.php (la lista
     * de todo lo que el sistema guarda), menos las que no deben respaldarse.
     *
     * @return list<string>
     */
    private function colecciones(): array
    {
        return array_values(array_diff(array_keys(require database_path('mongo/indices.php')), self::EXCLUIDAS));
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

        if (($cabecera['driver'] ?? null) !== self::MOTOR) {
            throw new ReglaDeNegocioException(
                'El respaldo es de una base '.($cabecera['driver'] ?? '?').' y esta instalación usa '.self::MOTOR.'.',
                'backup_file'
            );
        }

        // Un respaldo de una versión anterior tiene menos colecciones y se restaura bien;
        // uno de una versión más nueva trae colecciones que esta no conoce.
        if (array_diff($cabecera['colecciones'] ?? [], $this->colecciones()) !== []) {
            throw new ReglaDeNegocioException(
                'El respaldo es de otra versión de la estructura de la base. Actualizá el sistema a la versión con que se creó antes de restaurarlo.',
                'backup_file'
            );
        }
    }

    /**
     * @param  resource  $gz
     * @param  list<string>  $colecciones
     * @param  array<string,mixed>  $opciones
     */
    private function insertarDocumentos($gz, array $colecciones, array $opciones): void
    {
        $conocidas = array_flip($colecciones);
        $coleccion = '';
        $lote = [];

        while (($linea = gzgets($gz)) !== false) {
            // La línea es `{"t":"<colección>","f":<documento en JSON extendido>}`.
            if (! preg_match('/^\{"t":("(?:[^"\\\\]|\\\\.)*"),"f":(\{.*\})\}\s*$/s', $linea, $partes)
                || ! isset($conocidas[json_decode($partes[1])])) {
                throw new ReglaDeNegocioException('El respaldo está dañado o no pertenece a este sistema.', 'backup_file');
            }

            if (json_decode($partes[1]) !== $coleccion || count($lote) >= self::LOTE) {
                $this->insertarLote($coleccion, $lote, $opciones);
                $coleccion = json_decode($partes[1]);
                $lote = [];
            }

            try {
                $lote[] = Document::fromJSON($partes[2]);
            } catch (\Throwable) {
                throw new ReglaDeNegocioException('El respaldo está dañado o no pertenece a este sistema.', 'backup_file');
            }
        }

        $this->insertarLote($coleccion, $lote, $opciones);
    }

    /**
     * @param  list<Document>  $documentos
     * @param  array<string,mixed>  $opciones
     */
    private function insertarLote(string $coleccion, array $documentos, array $opciones): void
    {
        if ($documentos !== []) {
            $this->base()->selectCollection($coleccion)->insertMany($documentos, $opciones);
        }
    }
}
