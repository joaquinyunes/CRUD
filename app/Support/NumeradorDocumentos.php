<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Genera el próximo número correlativo de un comprobante (VTA-00001, etc.).
 *
 * Es el único lugar del sistema donde se decide un número de comprobante.
 *
 * Tiene que llamarse dentro de una transacción: el bloqueo de la fila del
 * contador se libera al terminarla, y es ese bloqueo el que impide que dos
 * ventas simultáneas se lleven el mismo número. Si la transacción se revierte,
 * el contador vuelve atrás con ella y la serie no deja huecos.
 */
class NumeradorDocumentos
{
    public static function proximo(string $tabla, string $prefijo, int $digitos = 5): string
    {
        if (DB::transactionLevel() === 0) {
            throw new RuntimeException(
                'NumeradorDocumentos::proximo() tiene que ejecutarse dentro de una transacción.'
            );
        }

        $serie = "{$tabla}:{$prefijo}";

        self::asegurarContador($serie, $tabla, $prefijo);

        $ultimo = (int) DB::table('contadores')
            ->where('serie', $serie)
            ->lockForUpdate()
            ->value('ultimo');

        $proximo = $ultimo + 1;

        DB::table('contadores')
            ->where('serie', $serie)
            ->update(['ultimo' => $proximo, 'updated_at' => now()]);

        return $prefijo.'-'.str_pad((string) $proximo, $digitos, '0', STR_PAD_LEFT);
    }

    /**
     * Crea la fila del contador la primera vez que se usa la serie, arrancando
     * desde el último número que ya exista en la tabla de comprobantes (para no
     * repetir números en una base que viene de la versión anterior).
     */
    private static function asegurarContador(string $serie, string $tabla, string $prefijo): void
    {
        if (DB::table('contadores')->where('serie', $serie)->exists()) {
            return;
        }

        // insertOrIgnore y no insert: dos procesos pueden llegar acá a la vez.
        DB::table('contadores')->insertOrIgnore([
            'serie'      => $serie,
            'ultimo'     => self::ultimoEnUso($tabla, $prefijo),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * El número más alto ya emitido para el prefijo.
     *
     * El corte y la comparación se hacen en PHP a propósito: hacerlo en SQL
     * obliga a un CAST, y la sintaxis del CAST cambia en cada motor (MySQL usa
     * UNSIGNED, que en PostgreSQL ni siquiera existe como tipo). Corre una sola
     * vez por serie, cuando se crea el contador.
     */
    private static function ultimoEnUso(string $tabla, string $prefijo): int
    {
        $desde = strlen($prefijo) + 1;

        return (int) DB::table($tabla)
            ->where('numero', 'like', "{$prefijo}-%")
            ->pluck('numero')
            ->map(fn ($numero) => (int) substr((string) $numero, $desde))
            ->max();
    }
}
