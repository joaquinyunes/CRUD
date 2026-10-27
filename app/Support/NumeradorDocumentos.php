<?php

namespace App\Support;

use App\Support\Mongo\Contadores;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Genera el próximo número correlativo de un comprobante (VTA-00001, etc.).
 *
 * Es el único lugar del sistema donde se decide un número de comprobante.
 *
 * Tiene que llamarse dentro de una transacción de MongoDB: el contador se saca
 * con un findOneAndUpdate + $inc (atómico), y como es parte de la transacción,
 * si esta se revierte el contador vuelve atrás con ella y la serie no deja
 * huecos. Si dos ventas simultáneas chocan sobre el contador, Mongo aborta una
 * y la transacción se reintenta con el número siguiente.
 */
class NumeradorDocumentos
{
    public static function proximo(string $tabla, string $prefijo, int $digitos = 5): string
    {
        if (DB::connection('mongodb')->transactionLevel() === 0) {
            throw new RuntimeException(
                'NumeradorDocumentos::proximo() tiene que ejecutarse dentro de una transacción.'
            );
        }

        $serie = "{$tabla}:{$prefijo}";

        // Si la serie recién nace, arranca desde el último número que ya exista en
        // la colección de comprobantes (para no repetir en una base que viene de antes).
        $desde = Contadores::existeNumero($serie) ? 0 : self::ultimoEnUso($tabla, $prefijo);

        $proximo = Contadores::siguienteNumero($serie, $desde);

        return $prefijo.'-'.str_pad((string) $proximo, $digitos, '0', STR_PAD_LEFT);
    }

    /**
     * El número más alto ya emitido para el prefijo.
     *
     * El corte y la comparación se hacen en PHP: corre una sola vez por serie,
     * cuando se crea el contador.
     */
    private static function ultimoEnUso(string $tabla, string $prefijo): int
    {
        $desde = strlen($prefijo) + 1;

        return (int) DB::connection('mongodb')->table($tabla)
            ->where('numero', 'like', "{$prefijo}-%")
            ->pluck('numero')
            ->map(fn ($numero) => (int) substr((string) $numero, $desde))
            ->max();
    }
}
