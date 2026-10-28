<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $_id
 * @property mixed $numero
 * @property mixed $cliente_id
 * @property mixed $deposito_id
 * @property Carbon|null $fecha
 * @property string $total
 * @property string $subtotal
 * @property string $descuento
 * @property mixed $descuento_tipo
 * @property string $impuesto
 * @property string $total_final
 * @property string $pagado
 * @property mixed $estado_pago
 * @property mixed $estado
 * @property mixed $motivo_anulacion
 * @property bool $stock_aplicado
 * @property mixed $user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Venta extends ModeloMongo
{
    use Auditable, HasFactory;

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

    /** @return BelongsTo<Cliente, $this> */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /** @return BelongsTo<Deposito, $this> */
    public function deposito(): BelongsTo
    {
        return $this->belongsTo(Deposito::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<VentaDetalle, $this> */
    public function detalles(): HasMany
    {
        return $this->hasMany(VentaDetalle::class);
    }

    /** @return HasMany<VentaPago, $this> */
    public function pagos(): HasMany
    {
        return $this->hasMany(VentaPago::class);
    }

    /** @return HasMany<Devolucion, $this> */
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
        if (! $buscar) {
            return $query;
        }

        // whereHas no existe entre colecciones: se buscan primero los clientes.
        $clienteIds = Cliente::where(fn ($q) => $q->where('nombre', 'like', "%{$buscar}%")
            ->orWhere('apellido', 'like', "%{$buscar}%"))
            ->pluck('id')->all();

        return $query->where(fn ($q) => $q->whereIn('cliente_id', $clienteIds)
            ->orWhere('numero', 'like', "%{$buscar}%"));
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
