<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venta extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero',
        'cliente_id',
        'fecha',
        'total',
        'subtotal',
        'descuento',
        'descuento_tipo',
        'impuesto',
        'total_final',
        'estado',
        'user_id',
    ];

    protected $casts = [
        'fecha'       => 'date',
        'total'       => 'decimal:2',
        'subtotal'    => 'decimal:2',
        'descuento'   => 'decimal:2',
        'impuesto'    => 'decimal:2',
        'total_final' => 'decimal:2',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(VentaDetalle::class);
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(VentaPago::class);
    }

    public function scopeBuscar($query, ?string $buscar)
    {
        if (!$buscar) {
            return $query;
        }

        return $query->whereHas('cliente', function ($q) use ($buscar) {
            $q->where('nombre', 'like', "%{$buscar}%")
              ->orWhere('apellido', 'like', "%{$buscar}%");
        })->orWhere('numero', 'like', "%{$buscar}%");
    }

    public function scopeParaFecha($query, ?string $desde, ?string $hasta)
    {
        if ($desde) {
            $query->where('fecha', '>=', $desde);
        }
        if ($hasta) {
            $query->where('fecha', '<=', $hasta);
        }

        return $query;
    }

    public function scopeParaEstado($query, ?string $estado)
    {
        if ($estado) {
            $query->where('estado', $estado);
        }

        return $query;
    }
}
