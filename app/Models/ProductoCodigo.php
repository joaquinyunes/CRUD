<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductoCodigo extends Model
{
    protected $table = 'producto_codigos';

    protected $fillable = [
        'producto_id',
        'codigo',
        'descripcion',
        'factor',
        'principal',
    ];

    protected $casts = [
        'factor' => 'decimal:3',
        'principal' => 'boolean',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
