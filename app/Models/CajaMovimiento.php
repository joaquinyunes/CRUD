<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $_id
 * @property mixed $caja_sesion_id
 * @property mixed $tipo
 * @property mixed $concepto
 * @property string $monto
 * @property mixed $referencia_tipo
 * @property mixed $referencia_id
 * @property mixed $user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CajaMovimiento extends ModeloMongo
{
    protected $table = 'caja_movimientos';

    protected $fillable = [
        'caja_sesion_id',
        'tipo',
        'concepto',
        'monto',
        'referencia_tipo',
        'referencia_id',
        'user_id',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
    ];

    /** @return BelongsTo<CajaSesion, $this> */
    public function sesion(): BelongsTo
    {
        return $this->belongsTo(CajaSesion::class, 'caja_sesion_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
