<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Support\CalculadorTotales;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El cálculo de totales lee el IVA de la configuración, así que necesita base
 * de datos. Estas son las cuentas que tienen que cerrar en cada comprobante.
 */
class CalculadorTotalesTest extends TestCase
{
    use RefreshDatabase;

    private function sinImpuesto(): void
    {
        Setting::establecer('sistema_impuesto_habilitado', '0');
    }

    private function conIva(string $porcentaje): void
    {
        Setting::establecer('sistema_impuesto_habilitado', '1');
        Setting::establecer('sistema_iva', $porcentaje);
    }

    public function test_suma_las_lineas_sin_descuento_ni_impuesto(): void
    {
        $this->sinImpuesto();

        $r = CalculadorTotales::calcular([
            ['cantidad' => 2, 'precio' => 150.00],
            ['cantidad' => 3, 'precio' => 100.00],
        ], null, 0);

        $this->assertSame(600.00, $r['subtotal']);
        $this->assertSame(0.0, $r['descuento']);
        $this->assertSame(600.00, $r['total']);
    }

    public function test_descuento_por_porcentaje(): void
    {
        $this->sinImpuesto();

        $r = CalculadorTotales::calcular(
            [['cantidad' => 1, 'precio' => 1000.00]],
            'porcentaje',
            10
        );

        $this->assertSame(100.00, $r['descuento']);
        $this->assertSame(900.00, $r['base_imponible']);
        $this->assertSame(900.00, $r['total']);
    }

    public function test_descuento_por_monto_fijo(): void
    {
        $this->sinImpuesto();

        $r = CalculadorTotales::calcular(
            [['cantidad' => 1, 'precio' => 1000.00]],
            'monto',
            250.00
        );

        $this->assertSame(250.00, $r['descuento']);
        $this->assertSame(750.00, $r['total']);
    }

    public function test_el_descuento_nunca_supera_el_subtotal(): void
    {
        $this->sinImpuesto();

        $r = CalculadorTotales::calcular(
            [['cantidad' => 1, 'precio' => 500.00]],
            'monto',
            900.00
        );

        $this->assertSame(500.00, $r['descuento']);
        $this->assertSame(0.0, $r['base_imponible']);
        $this->assertSame(0.0, $r['total']);
    }

    public function test_un_descuento_negativo_se_ignora(): void
    {
        $this->sinImpuesto();

        $r = CalculadorTotales::calcular(
            [['cantidad' => 1, 'precio' => 500.00]],
            'monto',
            -100.00
        );

        $this->assertSame(0.0, $r['descuento']);
        $this->assertSame(500.00, $r['total']);
    }

    public function test_el_impuesto_se_calcula_sobre_la_base_ya_descontada(): void
    {
        $this->conIva('21');

        $r = CalculadorTotales::calcular(
            [['cantidad' => 1, 'precio' => 1000.00]],
            'porcentaje',
            10
        );

        $this->assertSame(900.00, $r['base_imponible']);
        $this->assertSame(189.00, $r['impuesto']);
        $this->assertSame(1089.00, $r['total']);
    }

    public function test_con_el_impuesto_deshabilitado_no_se_suma_iva(): void
    {
        $this->sinImpuesto();

        $r = CalculadorTotales::calcular([['cantidad' => 1, 'precio' => 1000.00]], null, 0);

        $this->assertSame(0.0, $r['impuesto']);
        $this->assertSame(1000.00, $r['total']);
    }

    public function test_redondea_a_dos_decimales(): void
    {
        $this->conIva('21');

        $r = CalculadorTotales::calcular([['cantidad' => 3, 'precio' => 33.33]], null, 0);

        $this->assertSame(99.99, $r['subtotal']);
        $this->assertSame(21.00, $r['impuesto']);
        $this->assertSame(120.99, $r['total']);
    }
}
