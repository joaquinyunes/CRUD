<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Punto único de cálculo de totales para ventas y compras.
 * Evita la duplicación de la fórmula de descuento/impuesto en los controllers.
 */
class CalculadorTotales
{
    /**
     * @param  array<int,array{cantidad:int|float,precio:int|float}>  $lineas
     * @return array{subtotal:float,descuento:float,base_imponible:float,impuesto:float,total:float}
     */
    public static function calcular(array $lineas, ?string $descuentoTipo, float $descuentoValor): array
    {
        $subtotal = 0.0;
        foreach ($lineas as $l) {
            $subtotal += (float) $l['cantidad'] * (float) $l['precio'];
        }
        $subtotal = round($subtotal, 2);

        $descuento = $descuentoTipo === 'porcentaje'
            ? $subtotal * $descuentoValor / 100
            : $descuentoValor;
        $descuento = min(round(max($descuento, 0), 2), $subtotal);

        $base = round($subtotal - $descuento, 2);

        $ivaHabilitado = Setting::obtener('sistema_impuesto_habilitado', '1') === '1';
        $ivaPorcentaje = (float) Setting::obtener('sistema_iva', '21');
        $impuesto = $ivaHabilitado ? round($base * $ivaPorcentaje / 100, 2) : 0.0;

        return [
            'subtotal'       => $subtotal,
            'descuento'      => $descuento,
            'base_imponible' => $base,
            'impuesto'       => $impuesto,
            'total'          => round($base + $impuesto, 2),
        ];
    }

    /**
     * Determina el estado de pago a partir del total y lo abonado.
     */
    public static function estadoPago(float $total, float $pagado): string
    {
        if ($pagado <= 0) {
            return 'impago';
        }

        return $pagado + 0.01 >= $total ? 'pagado' : 'parcial';
    }
}
