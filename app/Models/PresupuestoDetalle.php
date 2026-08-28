<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresupuestoDetalle extends Model
{
    public $timestamps = false;

    protected $table = 'presupuestos_detalle';

    protected $fillable = ['presupuesto_id', 'producto_id', 'cantidad', 'precio', 'subtotal'];

    protected $casts = [
        'precio'   => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
