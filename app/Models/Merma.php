<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Merma extends Model
{
    protected $table = 'mermas';

    protected $fillable = [
        'producto_id', 'deposito_id', 'cantidad', 'motivo', 'costo', 'observaciones', 'user_id',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'costo' => 'decimal:2',
    ];

    public const MOTIVOS = ['rotura', 'vencimiento', 'robo', 'consumo_interno', 'ajuste'];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function deposito(): BelongsTo
    {
        return $this->belongsTo(Deposito::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
