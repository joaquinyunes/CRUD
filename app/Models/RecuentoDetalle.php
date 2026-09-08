<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecuentoDetalle extends Model
{
    public $timestamps = false;

    protected $table = 'recuento_detalle';

    protected $fillable = ['recuento_id', 'producto_id', 'stock_sistema', 'contado', 'diferencia'];

    protected $casts = [
        'stock_sistema' => 'integer',
        'contado' => 'integer',
        'diferencia' => 'integer',
    ];

    public function recuento(): BelongsTo
    {
        return $this->belongsTo(Recuento::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
