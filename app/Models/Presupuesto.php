<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $_id
 * @property mixed $numero
 * @property mixed $cliente_id
 * @property Carbon|null $fecha
 * @property mixed $validez_dias
 * @property string $subtotal
 * @property string $descuento
 * @property mixed $descuento_tipo
 * @property string $impuesto
 * @property string $total
 * @property mixed $estado
 * @property mixed $venta_id
 * @property mixed $observaciones
 * @property mixed $user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Presupuesto extends ModeloMongo
{
    use Auditable;

    protected $fillable = [
        'numero', 'cliente_id', 'fecha', 'validez_dias',
        'subtotal', 'descuento', 'descuento_tipo', 'impuesto', 'total',
        'estado', 'venta_id', 'observaciones', 'user_id',
    ];

    protected $casts = [
        'fecha'     => 'date',
        'subtotal'  => 'decimal:2',
        'descuento' => 'decimal:2',
        'impuesto'  => 'decimal:2',
        'total'     => 'decimal:2',
    ];

    /** @return BelongsTo<Cliente, $this> */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /** @return BelongsTo<Venta, $this> */
    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<PresupuestoDetalle, $this> */
    public function detalles(): HasMany
    {
        return $this->hasMany(PresupuestoDetalle::class);
    }

    public function estaVencido(): bool
    {
        return in_array($this->estado, ['borrador', 'enviado'], true)
            && $this->fecha->copy()->addDays($this->validez_dias)->isPast();
    }

    public function puedeConvertirse(): bool
    {
        return ! $this->venta_id && in_array($this->estado, ['borrador', 'enviado', 'aceptado'], true);
    }
}
