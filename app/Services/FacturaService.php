<?php

namespace App\Services;

use App\Contracts\FacturadorElectronico;
use App\Models\ComprobanteAfip;
use App\Models\Setting;
use App\Models\Venta;
use Illuminate\Support\Collection;
use RuntimeException;

class FacturaService
{
    public function __construct(private FacturadorElectronico $facturador) {}

    public function automatica(): bool
    {
        return Setting::obtener('afip_facturar_automatico', '1') === '1';
    }

    /**
     * Emite el comprobante fiscal de una venta. Idempotente: si ya tiene un
     * comprobante autorizado lo devuelve.
     */
    public function facturar(Venta $venta, ?int $tipoComprobante = null, ?int $docTipo = null, ?string $docNro = null): ComprobanteAfip
    {
        if (in_array($venta->estado, ['anulada', 'cancelada'], true)) {
            throw new RuntimeException('No se puede facturar una venta anulada.');
        }

        $existente = ComprobanteAfip::where('venta_id', $venta->id)
            ->whereIn('resultado', ['A', 'simulado'])->first();
        if ($existente) {
            return $existente;
        }

        $tipoComprobante ??= (int) config('afip.comprobante_default', 6);
        [$docTipo, $docNro] = $this->documento($venta, $docTipo, $docNro);

        return $this->facturador->autorizar($venta, $tipoComprobante, $docTipo, $docNro);
    }

    /** @return array{0:int,1:string} [docTipo, docNro] */
    private function documento(Venta $venta, ?int $docTipo, ?string $docNro): array
    {
        if ($docTipo && $docNro) {
            return [$docTipo, $docNro];
        }

        $doc = $venta->cliente?->documento;
        if ($doc) {
            $limpio = preg_replace('/\D/', '', $doc);

            // 11 dígitos => CUIT (80); si no, DNI (96).
            return strlen($limpio) === 11 ? [80, $limpio] : [96, $limpio];
        }

        return [99, '0']; // Consumidor final
    }

    /** @return Collection<int,ComprobanteAfip> */
    public function libroIva(string $desde, string $hasta)
    {
        return ComprobanteAfip::with('venta.cliente')
            ->whereIn('resultado', ['A', 'simulado'])
            ->whereBetween('created_at', [$desde.' 00:00:00', $hasta.' 23:59:59'])
            ->orderBy('punto_venta')->orderBy('tipo_comprobante')->orderBy('numero')
            ->get();
    }
}
