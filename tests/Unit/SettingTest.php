<?php

namespace Tests\Unit;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_devuelve_el_valor_guardado(): void
    {
        Setting::establecer('ventas_prefijo_numero', 'FAC');

        $this->assertSame('FAC', Setting::obtener('ventas_prefijo_numero', 'VTA'));
    }

    public function test_el_default_no_queda_cacheado_pisando_a_otro_default(): void
    {
        $this->assertSame('VTA', Setting::obtener('prefijo', 'VTA'));

        // Otra parte del sistema pide la misma clave con otro default: tiene que
        // recibir el suyo, no el de la llamada anterior.
        $this->assertSame('COM', Setting::obtener('prefijo', 'COM'));
    }

    public function test_un_valor_cargado_despues_se_ve_enseguida(): void
    {
        $this->assertSame('VTA', Setting::obtener('ventas_prefijo_numero', 'VTA'));

        Setting::establecer('ventas_prefijo_numero', 'FAC');

        $this->assertSame('FAC', Setting::obtener('ventas_prefijo_numero', 'VTA'));
    }

    public function test_cambiar_un_valor_invalida_la_cache(): void
    {
        Setting::establecer('impuesto_porcentaje', '21');
        $this->assertSame('21', Setting::obtener('impuesto_porcentaje'));

        Setting::establecer('impuesto_porcentaje', '10.5');
        $this->assertSame('10.5', Setting::obtener('impuesto_porcentaje'));
    }
}
