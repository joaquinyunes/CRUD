<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoPasarela extends Model
{
    protected $table = 'pagos_pasarela';

    protected $fillable = [
        'venta_id', 'pasarela', 'external_id', 'external_ref', 'monto', 'estado',
        'qr_data', 'neto_acreditado', 'comision', 'fecha_acreditacion', 'raw',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'neto_acreditado' => 'decimal:2',
        'comision' => 'decimal:2',
        'fecha_acreditacion' => 'date',
        'raw' => 'array',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function aprobado(): bool
    {
        return $this->estado === 'aprobado';
    }

    public function scopePendientes($q)
    {
        return $q->where('estado', 'pendiente');
    }
}
