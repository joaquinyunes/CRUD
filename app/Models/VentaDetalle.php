<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $_id
 * @property mixed $venta_id
 * @property mixed $producto_id
 * @property mixed $cantidad
 * @property string $precio
 * @property string $costo_unitario
 * @property string $subtotal
 */
class VentaDetalle extends ModeloMongo
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'ventas_detalle';

    protected $fillable = [
        'venta_id',
        'producto_id',
        'cantidad',
        'precio',
        'costo_unitario',
        'subtotal',
    ];

    protected $casts = [
        'precio'         => 'decimal:2',
        'costo_unitario' => 'decimal:2',
        'subtotal'       => 'decimal:2',
    ];

    /** @return BelongsTo<Venta, $this> */
    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    /** @return BelongsTo<Producto, $this> */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
