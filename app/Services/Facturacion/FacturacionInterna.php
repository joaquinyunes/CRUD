<?php

namespace App\Services\Facturacion;

use App\Contracts\FacturacionProvider;
use App\Models\Devolucion;
use App\Models\Venta;

/** Comprobante interno: sin CAE, sin validez fiscal. Es lo que hace el sistema hoy. */
class FacturacionInterna implements FacturacionProvider
{
    public function emitir(Venta $venta): array
    {
        return $this->interno((string) $venta->numero);
    }

    public function notaCredito(Devolucion $devolucion): array
    {
        return $this->interno((string) $devolucion->numero);
    }

    /** @return array{fiscal: bool, cae: null, cae_vencimiento: null, numero: string, proveedor: string} */
    private function interno(string $numero): array
    {
        return ['fiscal' => false, 'cae' => null, 'cae_vencimiento' => null, 'numero' => $numero, 'proveedor' => 'interno'];
    }
}
