<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Producto extends Model
{
    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'categoria_id',
        'marca',
        'precio_compra',
        'precio_venta',
        'stock',
        'stock_minimo',
        'imagen',
        'estado',
    ];

    protected $casts = [
        'precio_compra' => 'decimal:2',
        'precio_venta'  => 'decimal:2',
        'stock'         => 'integer',
        'stock_minimo'  => 'integer',
    ];

    public function scopeActivos($query)
    {
        return $query->where('estado', '!=', 'eliminado');
    }

    public function scopeStockCritico($query)
    {
        return $query->where('stock', '<=', \DB::raw('stock_minimo'))
                     ->where('estado', 'activo');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function getImagenUrlAttribute(): string
    {
        if ($this->imagen) {
            return asset('storage/' . $this->imagen);
        }
        return asset('images/producto-placeholder.png');
    }

    public function estaEnStockCritico(): bool
    {
        return $this->stock <= $this->stock_minimo;
    }
}
