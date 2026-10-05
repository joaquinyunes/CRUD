<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CajaMovimiento extends Model
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
