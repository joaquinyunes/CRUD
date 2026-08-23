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
        'deposito_id',
        'fecha',
        'total',
        'subtotal',
        'descuento',
        'descuento_tipo',
        'impuesto',
        'total_final',
        'pagado',
        'estado_pago',
        'estado',
        'motivo_anulacion',
        'stock_aplicado',
        'user_id',
    ];

    protected $casts = [
        'fecha'          => 'date',
        'total'          => 'decimal:2',
        'subtotal'       => 'decimal:2',
        'descuento'      => 'decimal:2',
        'impuesto'       => 'decimal:2',
        'total_final'    => 'decimal:2',
        'pagado'         => 'decimal:2',
        'stock_aplicado' => 'boolean',
    ];

    public const ESTADOS = ['pendiente', 'completada', 'cancelada', 'anulada'];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function deposito(): BelongsTo
    {
        return $this->belongsTo(Deposito::class);
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

    public function devoluciones(): HasMany
    {
        return $this->hasMany(Devolucion::class);
    }

    public function totalDevuelto(): float
    {
        return (float) $this->devoluciones()->where('estado', 'registrada')->sum('total');
    }

    /**
     * Saldo del documento. Positivo = el cliente debe; negativo = saldo a favor
     * del cliente (por ejemplo tras una devolución de una venta ya pagada).
     */
    public function saldoPendiente(): float
    {
        if (in_array($this->estado, ['cancelada', 'anulada'], true)) {
            return 0.0;
        }

        return round((float) $this->total_final - (float) $this->pagado - $this->totalDevuelto(), 2);
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
