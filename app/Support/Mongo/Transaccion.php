<?php

namespace App\Support\Mongo;

use Closure;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Transacción de MongoDB (necesita replica set) que se puede anidar.
 *
 * El transaction() del paquete no anida: un servicio que abre su propia
 * transacción dentro de la de VentaService rompería. Acá, si ya hay una en
 * curso, el código simplemente se suma a ella.
 */
class Transaccion
{
    /** Reintentos ante un choque de escrituras (dos ventas tocando el mismo contador). */
    private const INTENTOS = 25;

    public static function en(Closure $codigo): mixed
    {
        $conexion = DB::connection('mongodb');

        if ($conexion->transactionLevel() > 0) {
            return $codigo();
        }

        try {
            return $conexion->transaction($codigo, self::INTENTOS);
        } catch (Throwable $e) {
            // El paquete deja el contador de niveles en 1 si el código lanza una excepción.
            if ($conexion->transactionLevel() > 0) {
                $conexion->rollBack();
            }

            throw $e;
        }
    }
}
