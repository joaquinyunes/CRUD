<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComprobanteAfip extends Model
{
    protected $table = 'comprobantes_afip';

    protected $fillable = [
        'venta_id', 'tipo_comprobante', 'punto_venta', 'numero',
        'cae', 'cae_vencimiento', 'importe_total', 'importe_neto', 'importe_iva',
        'doc_tipo', 'doc_nro', 'resultado', 'observaciones',
    ];

    protected $casts = [
        'cae_vencimiento' => 'date',
        'importe_total' => 'decimal:2',
        'importe_neto' => 'decimal:2',
        'importe_iva' => 'decimal:2',
    ];

    public const LETRAS = [1 => 'A', 2 => 'A', 3 => 'A', 6 => 'B', 7 => 'B', 8 => 'B', 11 => 'C', 12 => 'C', 13 => 'C'];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function letra(): string
    {
        return self::LETRAS[$this->tipo_comprobante] ?? 'X';
    }

    public function autorizado(): bool
    {
        return in_array($this->resultado, ['A', 'simulado'], true);
    }

    public function numeroFormateado(): string
    {
        return sprintf('%s %04d-%08d', $this->letra(), $this->punto_venta, (int) $this->numero);
    }
}
