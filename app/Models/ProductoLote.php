<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductoLote extends Model
{
    protected $table = 'producto_lotes';

    protected $fillable = ['producto_id', 'deposito_id', 'lote', 'vencimiento', 'cantidad'];

    protected $casts = [
        'vencimiento' => 'date',
        'cantidad' => 'decimal:3',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function deposito(): BelongsTo
    {
        return $this->belongsTo(Deposito::class);
    }

    public function scopeConStock(Builder $q): Builder
    {
        return $q->where('cantidad', '>', 0);
    }

    /** Ordena FEFO: primero los que vencen antes (los sin fecha, al final). */
    public function scopeFefo(Builder $q): Builder
    {
        return $q->orderByRaw('vencimiento is null')->orderBy('vencimiento')->orderBy('id');
    }

    public function scopePorVencer(Builder $q, int $dias): Builder
    {
        return $q->whereNotNull('vencimiento')
            ->where('cantidad', '>', 0)
            ->whereDate('vencimiento', '<=', now()->addDays($dias)->toDateString());
    }

    public function diasParaVencer(): ?int
    {
        return $this->vencimiento ? (int) now()->startOfDay()->diffInDays($this->vencimiento, false) : null;
    }
}
