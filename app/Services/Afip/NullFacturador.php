<?php

namespace App\Services\Afip;

use App\Contracts\FacturadorElectronico;
use App\Models\ComprobanteAfip;
use App\Models\Venta;

/**
 * Facturador simulado: genera un CAE ficticio para poder operar sin
 * certificado AFIP. Los comprobantes quedan marcados como `simulado`.
 */
class NullFacturador implements FacturadorElectronico
{
    public function disponible(): bool
    {
        return true;
    }

    public function ultimoNumero(int $puntoVenta, int $tipoComprobante): int
    {
        return (int) ComprobanteAfip::where('punto_venta', $puntoVenta)
            ->where('tipo_comprobante', $tipoComprobante)
            ->whereIn('resultado', ['A', 'simulado'])
            ->max('numero');
    }

    public function autorizar(Venta $venta, int $tipoComprobante, int $docTipo, string $docNro): ComprobanteAfip
    {
        $pv = (int) config('afip.punto_venta', 1);
        $numero = $this->ultimoNumero($pv, $tipoComprobante) + 1;

        $neto = round((float) $venta->total_final - (float) $venta->impuesto, 2);

        return ComprobanteAfip::create([
            'venta_id' => $venta->id,
            'tipo_comprobante' => $tipoComprobante,
            'punto_venta' => $pv,
            'numero' => $numero,
            'cae' => str_pad((string) random_int(10000000000000, 99999999999999), 14, '0'),
            'cae_vencimiento' => now()->addDays(10)->toDateString(),
            'importe_total' => $venta->total_final,
            'importe_neto' => $neto,
            'importe_iva' => $venta->impuesto,
            'doc_tipo' => $docTipo,
            'doc_nro' => $docNro,
            'resultado' => 'simulado',
            'observaciones' => 'Comprobante simulado (sin conexión a AFIP).',
        ]);
    }
}
