<?php

namespace App\Services\Facturacion;

use App\Contracts\FacturacionProvider;
use App\Models\Devolucion;
use App\Models\Venta;
use RuntimeException;

/**
 * Proveedor de PRUEBA: imita lo que devuelve un proveedor certificado (CAE, vencimiento,
 * número) para ensayar el flujo completo sin tocar ARCA.
 *
 * Nunca es fiscal: el CAE lleva el prefijo «SIM-» y `fiscal` es siempre false, para que
 * ninguna pantalla ni PDF lo confunda con uno real. No se puede usar en producción.
 * Es determinista: la misma venta devuelve siempre el mismo comprobante (como haría un
 * proveedor real ante un reintento: no emite dos facturas por la misma venta).
 */
class FacturacionSimulada implements FacturacionProvider
{
    public function __construct(private bool $esProduccion = false)
    {
        if ($esProduccion) {
            throw new RuntimeException('La facturación simulada no puede usarse en producción: elegí un proveedor certificado.');
        }
    }

    public function emitir(Venta $venta): array
    {
        return $this->comprobante('FAC', (string) $venta->numero);
    }

    public function notaCredito(Devolucion $devolucion): array
    {
        return $this->comprobante('NCR', (string) $devolucion->numero);
    }

    /** @return array{fiscal: bool, cae: string, cae_vencimiento: string, numero: string, proveedor: string} */
    private function comprobante(string $tipo, string $numeroInterno): array
    {
        return [
            'fiscal'          => false,
            'cae'             => 'SIM-'.substr(hash('sha256', $tipo.'|'.$numeroInterno), 0, 14),
            'cae_vencimiento' => now()->addDays(10)->toDateString(),
            'numero'          => $tipo.'-'.$numeroInterno,
            'proveedor'       => 'simulado',
        ];
    }
}
