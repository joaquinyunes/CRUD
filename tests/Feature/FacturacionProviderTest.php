<?php

namespace Tests\Feature;

use App\Contracts\FacturacionProvider;
use App\Models\Devolucion;
use App\Models\Venta;
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
}
