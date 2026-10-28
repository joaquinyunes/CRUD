<?php

namespace App\Support\Mongo;

use Illuminate\Support\Facades\DB;
use MongoDB\Laravel\Connection;

/**
 * La conexión a MongoDB, ya tipada: `DB::connection()` devuelve la interfaz genérica de
 * Laravel y no conoce getDatabase(), getCollection() ni getSession().
 */
class Conexion
{
    public static function mongo(): Connection
    {
        $conexion = DB::connection('mongodb');

        assert($conexion instanceof Connection);

        return $conexion;
    }

    /**
     * True si hay una transacción de MongoDB en curso. Se pregunta a la sesión del driver y no
     * al contador de niveles del paquete, que queda en 1 si el código lanza una excepción.
     */
    public static function enTransaccion(): bool
    {
        return self::mongo()->getSession()?->isInTransaction() ?? false;
    }
}
