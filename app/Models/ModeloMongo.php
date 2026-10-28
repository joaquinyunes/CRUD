<?php

namespace App\Models;

use App\Support\Mongo\Consulta;
use App\Support\Mongo\Contadores;
use App\Support\Mongo\RelacionMuchosAMuchos;
use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Relations\BelongsToMany;

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
        if (Consulta::esClave($key)) {
            $value = Consulta::entero($value);
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * Igual que el del paquete, pero devolviendo RelacionMuchosAMuchos (acepta un id
     * suelto o una Collection en sync/attach). Con un modelo SQL del otro lado no cambia nada.
     *
     * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  class-string<TRelatedModel>  $related
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<TRelatedModel, $this>
     */
    public function belongsToMany($related, $collection = null, $foreignPivotKey = null, $relatedPivotKey = null, $parentKey = null, $relatedKey = null, $relation = null)
    {
        $relacion = parent::belongsToMany($related, $collection, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relation);

        if (! $relacion instanceof BelongsToMany || ! Model::isDocumentModel($related)) {
            return $relacion;
        }

        return new RelacionMuchosAMuchos(
            (new $related)->newQuery(),
            $this,
            $collection ?? (new $related)->getTable(),
            $foreignPivotKey ?: $this->getForeignKey().'s',
            $relatedPivotKey ?: (new $related)->getForeignKey().'s',
            $parentKey ?: $this->getKeyName(),
            $relatedKey ?: (new $related)->getKeyName(),
            $relation ?? $this->guessBelongsToManyRelation(),
        );
    }

    /**
     * Un modelo relacionado que todavía vive en SQL (compras, devoluciones...) hereda por
     * defecto la conexión de este modelo, o sea MongoDB, y la consulta SQL revienta. Se le
     * deja la conexión SQL por defecto hasta que se porte.
     */
    protected function newRelatedInstance($class)
    {
        return tap(new $class, function ($instancia) {
            if (! $instancia->getConnectionName()) {
                $instancia->setConnection(Model::isDocumentModel($instancia) ? $this->connection : config('database.default'));
            }
        });
    }

    protected function newBaseQueryBuilder()
    {
        $conexion = $this->getConnection();

        return new Consulta($conexion, $conexion->getQueryGrammar(), $conexion->getPostProcessor());
    }
}
