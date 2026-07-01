<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'producto_id',
        'tipo',
        'cantidad',
        'user_id',
        'referencia_tipo',
        'referencia_id',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeParaProducto($query, ?int $productoId)
    {
        if ($productoId) {
            $query->where('producto_id', $productoId);
        }

        return $query;
    }

    public function scopeParaTipo($query, ?string $tipo)
    {
        if ($tipo) {
            $query->where('tipo', $tipo);
        }

        return $query;
    }

    public function scopeParaFecha($query, ?string $desde, ?string $hasta)
    {
        if ($desde) {
            $query->where('created_at', '>=', $desde);
        }
        if ($hasta) {
            $query->where('created_at', '<=', $hasta . ' 23:59:59');
        }

        return $query;
    }
}
