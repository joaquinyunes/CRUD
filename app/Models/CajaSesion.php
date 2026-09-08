<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CajaSesion extends Model
{
    protected $table = 'caja_sesiones';

    protected $fillable = [
        'user_id',
        'terminal',
        'dia_comercial',
        'monto_inicial',
        'monto_final_declarado',
        'monto_final_sistema',
        'diferencia',
        'estado',
        'observaciones',
        'abierta_en',
        'cerrada_en',
    ];

    protected $casts = [
        'monto_inicial' => 'decimal:2',
        'monto_final_declarado' => 'decimal:2',
        'monto_final_sistema' => 'decimal:2',
        'diferencia' => 'decimal:2',
        'abierta_en' => 'datetime',
        'cerrada_en' => 'datetime',
        'dia_comercial' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(CajaMovimiento::class);
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'caja_sesion_id');
    }

    public function scopeAbierta($query)
    {
        return $query->where('estado', 'abierta');
    }

    public function totalIngresos(): float
    {
        return (float) $this->movimientos()->where('tipo', 'ingreso')->sum('monto');
    }

    public function totalEgresos(): float
    {
        return (float) $this->movimientos()->where('tipo', 'egreso')->sum('monto');
    }

    public function saldoEsperado(): float
    {
        return round((float) $this->monto_inicial + $this->totalIngresos() - $this->totalEgresos(), 2);
    }
}
