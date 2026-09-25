<?php

namespace Tests\Unit;

use App\Support\CalculadorTotales;
use PHPUnit\Framework\TestCase;

/**
 * El estado de pago de un comprobante es una función pura del total y de lo
 * abonado: no toca base de datos, así que se prueba como unidad.
 */
class EstadoPagoTest extends TestCase
{
    public function test_sin_pagos_queda_impago(): void
    {
        $this->assertSame('impago', CalculadorTotales::estadoPago(1000.00, 0.0));
    }

    public function test_un_pago_menor_al_total_queda_parcial(): void
    {
        $this->assertSame('parcial', CalculadorTotales::estadoPago(1000.00, 400.00));
    }

    public function test_pagar_el_total_exacto_queda_pagado(): void
    {
        $this->assertSame('pagado', CalculadorTotales::estadoPago(1000.00, 1000.00));
    }

    public function test_pagar_de_mas_queda_pagado(): void
    {
        $this->assertSame('pagado', CalculadorTotales::estadoPago(1000.00, 1200.00));
    }

    public function test_tolera_un_centavo_de_redondeo(): void
    {
        $this->assertSame('pagado', CalculadorTotales::estadoPago(1000.00, 999.99));
        $this->assertSame('parcial', CalculadorTotales::estadoPago(1000.00, 999.50));
    }

    public function test_un_pago_negativo_no_cuenta_como_pago(): void
    {
        $this->assertSame('impago', CalculadorTotales::estadoPago(1000.00, -50.00));
    }
}
