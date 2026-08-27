<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DevolucionDetalle extends Model
{
    public $timestamps = false;

    protected $table = 'devoluciones_detalle';

    protected $fillable = [
        'devolucion_id',
        'producto_id',
        'cantidad',
        'precio',
        'subtotal',
    ];

    protected $casts = [
        'precio'   => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function devolucion(): BelongsTo
    {
        return $this->belongsTo(Devolucion::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
