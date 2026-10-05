<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Punto único de cálculo de totales para ventas y compras.
 *
 * Toda la aritmética se hace en centavos enteros y recién al final se vuelve a
 * pesos. Con float, sumar y multiplicar importes arrastra error de
 * representación: 0.1 + 0.2 no da 0.3 en binario, y con suficientes líneas el
 * total impreso en el comprobante deja de coincidir con la suma de los
 * renglones. En centavos no hay nada que redondear: la cuenta es exacta por
 * construcción.
 *
 * La firma sigue entrando y saliendo en pesos porque así lo guarda la base
 * (decimal con dos decimales) y así lo esperan las vistas y la API.
 */
class CalculadorTotales
{
    /**
     * @param  array<int,array{cantidad:int|float,precio:int|float}>  $lineas
     * @return array{subtotal:float,descuento:float,base_imponible:float,impuesto:float,total:float}
     */
    public static function calcular(array $lineas, ?string $descuentoTipo, float $descuentoValor): array
    {
        $subtotal = 0;
        foreach ($lineas as $linea) {
            // La cantidad puede venir fraccionada (media hora, 1,5 kg), así que
            // el redondeo va sobre el importe de la línea, no sobre el precio.
            $subtotal += self::aCentavos((float) $linea['cantidad'] * (float) $linea['precio']);
        }

        $descuento = $descuentoTipo === 'porcentaje'
            ? self::porcentajeDe($subtotal, $descuentoValor)
            : self::aCentavos($descuentoValor);

        // Un descuento negativo no resta, y ninguno puede dejar el total bajo cero.
        $descuento = min(max($descuento, 0), $subtotal);

        $base = $subtotal - $descuento;
        $impuesto = self::impuestoHabilitado()
            ? self::porcentajeDe($base, self::porcentajeDeImpuesto())
            : 0;

        return [
            'subtotal'       => self::aPesos($subtotal),
            'descuento'      => self::aPesos($descuento),
            'base_imponible' => self::aPesos($base),
            'impuesto'       => self::aPesos($impuesto),
            'total'          => self::aPesos($base + $impuesto),
        ];
    }

    /**
     * Determina el estado de pago a partir del total y lo abonado.
     *
     * Tolera un centavo de diferencia: con pagos en varios métodos, repartir
     * un total impar deja un centavo colgado y el comprobante no puede quedar
     * eternamente "parcial" por eso.
     */
    public static function estadoPago(float $total, float $pagado): string
    {
        $pagadoCentavos = self::aCentavos($pagado);

        if ($pagadoCentavos <= 0) {
            return 'impago';
        }

        return $pagadoCentavos >= self::aCentavos($total) - 1 ? 'pagado' : 'parcial';
    }

    private static function aCentavos(float $pesos): int
    {
        return (int) round($pesos * 100);
    }

    private static function aPesos(int $centavos): float
    {
        return $centavos / 100;
    }

    /** El porcentaje puede tener decimales (10,5%), el resultado nunca. */
    private static function porcentajeDe(int $centavos, float $porcentaje): int
    {
        return (int) round($centavos * $porcentaje / 100);
    }

    private static function impuestoHabilitado(): bool
    {
        return Setting::obtener('sistema_impuesto_habilitado', '1') === '1';
    }

    private static function porcentajeDeImpuesto(): float
    {
        return (float) Setting::obtener('sistema_iva', '21');
    }
}
