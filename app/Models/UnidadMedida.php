<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MongoDB\Laravel\Eloquent\Model;

class UnidadMedida extends Model
{
    protected $connection = 'mongodb';

    protected $table = 'unidades_medida';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'abreviacion',
        'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    /**
     * Producto sigue en SQL. OJO: HybridRelations::hasMany() delega a
     * parent::hasMany() para relaciones Mongo -> SQL, y ese metodo (core de
     * Eloquent) copia la conexion del padre ('mongodb') al modelo
     * relacionado via newRelatedInstance() -> rompe la query. Se arma la
     * relacion a mano para que Producto conserve su propia conexion.
     * Nota: productos.unidad_medida_id es string (ObjectId), ver migracion
     * 2026_09_14_000003_mongo_ids_en_productos.
     */
    public function productos(): HasMany
    {
        return new HasMany(Producto::query(), $this, 'unidad_medida_id', '_id');
    }
}
