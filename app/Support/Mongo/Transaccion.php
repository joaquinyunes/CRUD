<?php

namespace App\Support\Mongo;

use Closure;
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
        $conexion = Conexion::mongo();

        if (Conexion::enTransaccion()) {
            return $codigo();
        }

        try {
            return $conexion->transaction($codigo, self::INTENTOS);
        } catch (Throwable $e) {
            // El paquete deja su contador de niveles en 1 si el código lanza una excepción.
            $conexion->rollBack();

            throw $e;
        }
    }
}
