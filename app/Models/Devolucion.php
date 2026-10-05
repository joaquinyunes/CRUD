<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Devolucion extends Model
{
    use Auditable;

    protected $table = 'devoluciones';

    protected $fillable = [
        'numero',
        'tipo',
        'venta_id',
        'compra_id',
        'fecha',
        'motivo',
        'total',
        'estado',
        'user_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'total' => 'decimal:2',
    ];

    /** @return HasMany<DevolucionDetalle, $this> */
    public function detalles(): HasMany
    {
        return $this->hasMany(DevolucionDetalle::class);
    }

    /** @return BelongsTo<Venta, $this> */
    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    /** @return BelongsTo<Compra, $this> */
    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeRegistradas($query)
    {
        return $query->where('estado', 'registrada');
    }
}
