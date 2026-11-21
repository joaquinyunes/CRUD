<?php

namespace Tests\Feature;

use App\Contracts\FacturacionProvider;
use App\Models\Devolucion;
use App\Models\Venta;
use App\Services\Facturacion\FacturacionSimulada;
use Tests\TestCase;

class FacturacionProviderTest extends TestCase
{
    public function test_sin_proveedor_elegido_el_comprobante_es_interno_y_nunca_trae_cae(): void
    {
        $proveedor = app(FacturacionProvider::class);

        $factura = $proveedor->emitir(new Venta(['numero' => 'V-000001']));
        $nota = $proveedor->notaCredito(new Devolucion(['numero' => 'D-000001']));

        foreach ([$factura, $nota] as $comprobante) {
            $this->assertFalse($comprobante['fiscal']);
            $this->assertNull($comprobante['cae']);
            $this->assertSame('interno', $comprobante['proveedor']);
        }
        $this->assertSame('V-000001', $factura['numero']);
    }

    public function test_el_simulado_imita_un_proveedor_pero_nunca_es_fiscal_y_es_determinista(): void
    {
        config(['sistema.facturacion.proveedor' => 'simulado']);
        $proveedor = app(FacturacionProvider::class);
        $venta = new Venta(['numero' => 'V-000007']);

        $a = $proveedor->emitir($venta);
        $b = $proveedor->emitir($venta);
        $nota = $proveedor->notaCredito(new Devolucion(['numero' => 'D-000007']));

        $this->assertSame($a['cae'], $b['cae'], 'Un reintento no puede emitir otro comprobante.');
        $this->assertFalse($a['fiscal']);
        $this->assertStringStartsWith('SIM-', $a['cae']);
        $this->assertSame('simulado', $a['proveedor']);
        $this->assertSame('FAC-V-000007', $a['numero']);
        $this->assertNotSame($a['cae'], $nota['cae']);
        $this->assertStringStartsWith('NCR-', $nota['numero']);
    }

    public function test_el_simulado_no_arranca_en_produccion(): void
    {
        $this->expectException(\RuntimeException::class);

        new FacturacionSimulada(true);
    }
}
