<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ListaPrecio extends Model
{
    protected $table = 'listas_precio';

    protected $fillable = ['nombre', 'ajuste_pct', 'activa', 'orden'];

    protected $casts = [
        'ajuste_pct' => 'decimal:2',
        'activa' => 'boolean',
        'orden' => 'integer',
    ];

    public function precios(): HasMany
    {
        return $this->hasMany(PrecioProducto::class);
    }

    public function scopeActivas($query)
    {
        return $query->where('activa', true)->orderBy('orden');
    }
}
