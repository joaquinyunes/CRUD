<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $_id
 * @property mixed $nombre
 * @property mixed $abreviacion
 * @property bool $estado
 */
class UnidadMedida extends ModeloMongo
{
    use Auditable;

    public $timestamps = false;

    protected $table = 'unidades_medida';

    protected $fillable = [
        'nombre',
        'abreviacion',
        'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    /** @return HasMany<Producto, $this> */
    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }
}
