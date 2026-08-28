<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdenCompraDetalle extends Model
{
    public $timestamps = false;

    protected $table = 'ordenes_compra_detalle';

    protected $fillable = ['orden_compra_id', 'producto_id', 'cantidad', 'cantidad_recibida', 'precio', 'subtotal'];

    protected $casts = [
        'precio'   => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function pendiente(): int
    {
        return max(0, $this->cantidad - $this->cantidad_recibida);
    }
}
