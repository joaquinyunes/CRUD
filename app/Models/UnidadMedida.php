<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnidadMedida extends Model
{
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

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }
}
