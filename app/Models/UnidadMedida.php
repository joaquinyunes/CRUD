<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnidadMedida extends Model
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
