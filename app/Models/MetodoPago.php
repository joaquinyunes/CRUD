<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetodoPago extends Model
{
    public $timestamps = false;

    protected $table = 'metodos_pago';

    protected $fillable = [
        'nombre',
        'codigo',
        'activo',
        'permite_vuelto',
        'orden',
    ];

    protected $casts = [
        'activo'         => 'boolean',
        'permite_vuelto' => 'boolean',
        'orden'          => 'integer',
    ];

    public function ventasPago(): HasMany
    {
        return $this->hasMany(VentaPago::class);
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true)->orderBy('orden');
    }
}
