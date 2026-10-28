<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $_id
 * @property mixed $devolucion_id
 * @property mixed $producto_id
 * @property mixed $cantidad
 * @property string $precio
 * @property string $subtotal
 */
class DevolucionDetalle extends ModeloMongo
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

    /** @return BelongsTo<Devolucion, $this> */
    public function devolucion(): BelongsTo
    {
        return $this->belongsTo(Devolucion::class);
    }

    /** @return BelongsTo<Producto, $this> */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
