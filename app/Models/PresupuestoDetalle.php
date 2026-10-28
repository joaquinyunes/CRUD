<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $_id
 * @property mixed $presupuesto_id
 * @property mixed $producto_id
 * @property mixed $cantidad
 * @property string $precio
 * @property string $subtotal
 */
class PresupuestoDetalle extends ModeloMongo
{
    public $timestamps = false;

    protected $table = 'presupuestos_detalle';

    protected $fillable = ['presupuesto_id', 'producto_id', 'cantidad', 'precio', 'subtotal'];

    protected $casts = [
        'precio'   => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /** @return BelongsTo<Producto, $this> */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
