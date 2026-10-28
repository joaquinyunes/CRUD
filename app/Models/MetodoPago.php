<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $_id
 * @property mixed $nombre
 * @property mixed $codigo
 * @property bool $activo
 * @property bool $permite_vuelto
 * @property int $orden
 */
class MetodoPago extends ModeloMongo
{
    use Auditable;

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

    /** @return HasMany<VentaPago, $this> */
    public function ventasPago(): HasMany
    {
        return $this->hasMany(VentaPago::class);
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true)->orderBy('orden');
    }
}
