<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompraPago extends Model
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
