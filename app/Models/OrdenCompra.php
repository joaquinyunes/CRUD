<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MongoDB\Laravel\Eloquent\HybridRelations;

class OrdenCompra extends Model
{
    // proveedor() apunta a un modelo Mongo; HybridRelations arma el puente.
    use HybridRelations;

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

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

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
