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
 * @property mixed $proveedor_id
 * @property mixed $deposito_id
 * @property Carbon|null $fecha
 * @property Carbon|null $fecha_entrega_estimada
 * @property string $total
 * @property mixed $estado
 * @property mixed $compra_id
 * @property mixed $observaciones
 * @property mixed $user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class OrdenCompra extends ModeloMongo
{
    use Auditable;

    protected $table = 'ordenes_compra';

    protected $fillable = [
        'numero', 'proveedor_id', 'deposito_id', 'fecha', 'fecha_entrega_estimada',
        'total', 'estado', 'compra_id', 'observaciones', 'user_id',
    ];

    protected $casts = [
        'fecha'                  => 'date',
        'fecha_entrega_estimada' => 'date',
        'total'                  => 'decimal:2',
    ];

    /** @return BelongsTo<Proveedor, $this> */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
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

    /** @return HasMany<OrdenCompraDetalle, $this> */
    public function detalles(): HasMany
    {
        return $this->hasMany(OrdenCompraDetalle::class, 'orden_compra_id');
    }

    public function totalmenteRecibida(): bool
    {
        return $this->detalles->every(fn ($d) => $d->cantidad_recibida >= $d->cantidad);
    }

    public function tieneRecepciones(): bool
    {
        return $this->detalles->contains(fn ($d) => $d->cantidad_recibida > 0);
    }
}
