<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $_id
 * @property mixed $venta_id
 * @property mixed $metodo_pago_id
 * @property string $monto
 * @property mixed $referencia
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class VentaPago extends ModeloMongo
{
    use HasFactory;

    protected $table = 'ventas_pago';

    protected $fillable = [
        'venta_id',
        'metodo_pago_id',
        'monto',
        'referencia',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
    ];

    /** @return BelongsTo<Venta, $this> */
    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    /** @return BelongsTo<MetodoPago, $this> */
    public function metodoPago(): BelongsTo
    {
        return $this->belongsTo(MetodoPago::class);
    }
}
