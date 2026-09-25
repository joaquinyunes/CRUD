<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Genera el próximo número correlativo para un documento (VTA-00001, etc.).
 * Debe llamarse dentro de una transacción para que el lockForUpdate sirva.
 */
class NumeradorDocumentos
{
    public static function proximo(string $tabla, string $prefijo, int $digitos = 5): string
    {
        $largo = strlen($prefijo) + 2;

        $ultimo = DB::table($tabla)
            ->where('numero', 'like', "{$prefijo}-%")
            ->orderByRaw("CAST(SUBSTRING(numero, {$largo}) AS UNSIGNED) DESC")
            ->lockForUpdate()
            ->value('numero');

        $n = $ultimo ? ((int) substr($ultimo, strlen($prefijo) + 1)) + 1 : 1;

        return $prefijo.'-'.str_pad((string) $n, $digitos, '0', STR_PAD_LEFT);
    }
}
