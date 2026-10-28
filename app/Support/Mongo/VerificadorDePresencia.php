<?php

namespace App\Support\Mongo;

use Illuminate\Validation\DatabasePresenceVerifier;

/**
 * Hace que las reglas `exists:mongodb.clientes,id` y `unique:` funcionen con ids
 * numéricos (D1): el id llega del formulario como texto y Mongo no compara "5"
 * con 5. Para el resto de las columnas se comporta como el verificador de Laravel.
 *
 * (El verificador que trae mongodb/laravel-mongodb compara con una expresión
 * regular, que nunca coincide con un número.)
 */
class VerificadorDePresencia extends DatabasePresenceVerifier
{
    public function getCount($collection, $column, $value, $excludeId = null, $idColumn = null, array $extra = [])
    {
        if (Consulta::esClave($column)) {
            $value = Consulta::entero($value);
        }

        if ($excludeId !== null && ctype_digit((string) $excludeId)) {
            $excludeId = Consulta::entero($excludeId);
        }

        return parent::getCount($collection, $column, $value, $excludeId, $idColumn, $extra);
    }

    public function getMultiCount($collection, $column, array $values, array $extra = [])
    {
        if (Consulta::esClave($column)) {
            $values = Consulta::entero($values);
        }

        return parent::getMultiCount($collection, $column, $values, $extra);
    }
}
