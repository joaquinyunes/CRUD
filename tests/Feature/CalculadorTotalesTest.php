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

    /**
     * El caso que justifica trabajar en centavos: con float, sumar cien veces
     * un importe de dos decimales arrastra error de representación y el total
     * deja de coincidir con la suma de los renglones.
     */
    public function test_cien_lineas_de_centavos_suman_exacto(): void
    {
        $this->sinImpuesto();

        $lineas = array_fill(0, 100, ['cantidad' => 1, 'precio' => 0.07]);

        $r = CalculadorTotales::calcular($lineas, null, 0);

        $this->assertSame(7.00, $r['subtotal']);
        $this->assertSame(7.00, $r['total']);
    }

    /**
     * Las partes cierran contra el total al centavo.
     *
     * La comparación se hace en centavos y no restando los float devueltos,
     * porque sumar float vuelve a arrastrar el error: 121,90 calculado como
     * base + impuesto en punto flotante da 121,89999999999999. Es exactamente
     * por eso que la cuenta de adentro no usa float, y por eso el total que
     * vale es el que devuelve esta clase y no uno rearmado por quien la llama.
     */
    public function test_las_partes_cierran_contra_el_total_al_centavo(): void
    {
        $this->conIva('21');

        $r = CalculadorTotales::calcular([
            ['cantidad' => 7, 'precio' => 19.99],
            ['cantidad' => 3, 'precio' => 0.33],
        ], 'porcentaje', 13.5);

        $centavos = fn (float $pesos): int => (int) round($pesos * 100);

        $this->assertSame(
            $centavos($r['subtotal']) - $centavos($r['descuento']),
            $centavos($r['base_imponible'])
        );
        $this->assertSame(
            $centavos($r['base_imponible']) + $centavos($r['impuesto']),
            $centavos($r['total'])
        );
    }

    public function test_un_porcentaje_con_decimales_no_deja_fracciones_de_centavo(): void
    {
        $this->sinImpuesto();

        $r = CalculadorTotales::calcular(
            [['cantidad' => 1, 'precio' => 333.33]],
            'porcentaje',
            10.5
        );

        // 333,33 * 10,5% = 34,99965 -> 35,00
        $this->assertSame(35.00, $r['descuento']);
        $this->assertSame(298.33, $r['total']);
    }

    public function test_una_cantidad_fraccionada_redondea_la_linea(): void
    {
        $this->sinImpuesto();

        $r = CalculadorTotales::calcular([['cantidad' => 1.5, 'precio' => 10.01]], null, 0);

        // 1,5 * 10,01 = 15,015 -> 15,02
        $this->assertSame(15.02, $r['subtotal']);
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
