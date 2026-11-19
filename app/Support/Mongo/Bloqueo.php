<?php

namespace App\Support\Mongo;

use App\Exceptions\ReglaDeNegocioException;
use Illuminate\Database\Eloquent\Model;

/**
 * Candado optimista sobre un documento, para usar DENTRO de Transaccion::en.
 *
 * Dos transacciones que leen "lo ya devuelto" y luego insertan cada una su devolución
 * no se pisan entre sí (escriben documentos distintos) y las dos pasan el control: se
 * devuelve más de lo vendido. Escribir las dos el MISMO documento padre sí provoca un
 * conflicto de escritura en MongoDB: una transacción se reintenta y, al releer, ya ve
 * lo que dejó la otra. La condición viaja en el propio update, así que también cierra
 * el hueco entre «leí el estado» y «actué».
 */
class Bloqueo
{
    /**
     * @param  array<string,mixed>  $condiciones  campo => valor (o [operador, valor]) que el documento debe cumplir ahora
     *
     * @throws ReglaDeNegocioException si el documento ya no cumple las condiciones
     */
    public static function tomar(Model $documento, array $condiciones, string $mensaje): void
    {
        $consulta = $documento->newQuery()->where('_id', $documento->getKey());

        foreach ($condiciones as $campo => $valor) {
            // [operador, valor] para condiciones como ['!=', 'anulada'].
            is_array($valor) ? $consulta->where($campo, $valor[0], $valor[1]) : $consulta->where($campo, $valor);
        }

        if ($consulta->increment('bloqueo') === 0) {
            throw new ReglaDeNegocioException($mensaje);
        }

        $documento->refresh();
    }
}
