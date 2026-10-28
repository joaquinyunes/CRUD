<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $_id
 * @property mixed $compra_id
 * @property mixed $metodo_pago_id
 * @property string $monto
 * @property mixed $referencia
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CompraPago extends ModeloMongo
{
    use HasFactory;

    protected $table = 'compras_pago';

    protected $fillable = [
        'compra_id',
        'metodo_pago_id',
        'monto',
        'referencia',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
    ];

    /** @return BelongsTo<Compra, $this> */
    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class);
    }

    /** @return BelongsTo<MetodoPago, $this> */
    public function metodoPago(): BelongsTo
    {
        return $this->belongsTo(MetodoPago::class);
    }
}
