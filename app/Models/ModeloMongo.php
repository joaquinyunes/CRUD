<?php

namespace App\Models;

use App\Support\Mongo\Consulta;
use App\Support\Mongo\Contadores;
use MongoDB\Laravel\Eloquent\Model;

/**
 * Base de los modelos que viven en MongoDB.
 *
 * Decisión D1: el id es un entero sacado de una secuencia (colección
 * `contadores`) y no un ObjectId, para que rutas, vistas, formularios y
 * `in_array` con ids sigan funcionando igual que con SQL.
 */
abstract class ModeloMongo extends Model
{
    protected $connection = 'mongodb';

    protected $keyType = 'int';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $modelo) {
            if ($modelo->getAttribute('_id') === null) {
                $modelo->setAttribute('_id', Contadores::siguienteId($modelo->getTable()));
            }
        });
    }

    /** Un id o clave foránea que llega como texto ("7") se guarda como entero. */
    public function setAttribute($key, $value)
    {
        if (is_string($key) && Consulta::esClave($key)) {
            $value = Consulta::entero($value);
        }

        return parent::setAttribute($key, $value);
    }

    protected function newBaseQueryBuilder()
    {
        $conexion = $this->getConnection();

        return new Consulta($conexion, $conexion->getQueryGrammar(), $conexion->getPostProcessor());
    }
}
