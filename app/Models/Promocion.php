<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Promocion extends Model
{
    protected $table = 'promociones';

    protected $fillable = [
        'nombre', 'tipo', 'valor', 'n', 'm', 'alcance',
        'producto_id', 'categoria_id', 'desde', 'hasta',
        'hora_desde', 'hora_hasta', 'dias', 'activa', 'prioridad',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'n' => 'integer',
        'm' => 'integer',
        'desde' => 'date',
        'hasta' => 'date',
        'dias' => 'array',
        'activa' => 'boolean',
        'prioridad' => 'integer',
    ];

    public const TIPOS = ['porcentaje', 'monto', 'precio_fijo', 'nxm'];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    /** Promociones vigentes en un momento dado (fecha + franja horaria + día). */
    public function scopeVigentes(Builder $query, ?Carbon $momento = null): Builder
    {
        $momento ??= now();
        $hora = $momento->format('H:i:s');

        return $query->where('activa', true)
            ->where(fn ($q) => $q->whereNull('desde')->orWhere('desde', '<=', $momento->toDateString()))
            ->where(fn ($q) => $q->whereNull('hasta')->orWhere('hasta', '>=', $momento->toDateString()))
            ->where(fn ($q) => $q->whereNull('hora_desde')->orWhere('hora_desde', '<=', $hora))
            ->where(fn ($q) => $q->whereNull('hora_hasta')->orWhere('hora_hasta', '>=', $hora))
            ->orderByDesc('prioridad');
    }

    public function aplicaADia(?Carbon $momento = null): bool
    {
        if (empty($this->dias)) {
            return true;
        }

        return in_array(($momento ?? now())->dayOfWeekIso, array_map('intval', $this->dias), true);
    }

    /**
     * Descuento total (sobre el importe de la línea) para `$cantidad` unidades
     * a `$precioUnitario`. Nunca supera el importe de la línea.
     */
    public function descuentoPara(float $cantidad, float $precioUnitario): float
    {
        $bruto = $cantidad * $precioUnitario;

        $desc = match ($this->tipo) {
            'porcentaje' => $bruto * (float) $this->valor / 100,
            'monto' => (float) $this->valor * $cantidad,
            'precio_fijo' => max(0, $bruto - (float) $this->valor * $cantidad),
            'nxm' => $this->descuentoNxM($cantidad, $precioUnitario),
            default => 0,
        };

        return round(min(max($desc, 0), $bruto), 2);
    }

    private function descuentoNxM(float $cantidad, float $precioUnitario): float
    {
        $n = max(1, (int) $this->n);
        $m = max(0, (int) $this->m);
        if ($m >= $n) {
            return 0;
        }
        $grupos = intdiv((int) $cantidad, $n);
        $gratis = $grupos * ($n - $m);

        return $gratis * $precioUnitario;
    }
}
