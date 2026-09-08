<?php

namespace App\Services\Pasarela;

use App\Contracts\PasarelaPago;
use App\Models\PagoPasarela;

/**
 * Pasarela simulada: el cobro QR se aprueba al instante. Sirve para probar
 * el flujo del POS sin credenciales de Mercado Pago.
 */
class NullPasarela implements PasarelaPago
{
    public function disponible(): bool
    {
        return true;
    }

    public function crearCobro(float $monto, string $referencia): PagoPasarela
    {
        return PagoPasarela::create([
            'pasarela' => 'mercadopago',
            'external_id' => 'SIM-'.strtoupper(bin2hex(random_bytes(6))),
            'external_ref' => $referencia,
            'monto' => round($monto, 2),
            'estado' => 'aprobado', // simulado: aprueba al toque
            'qr_data' => 'simulado://cobro/'.$referencia,
            'neto_acreditado' => round($monto, 2),
            'comision' => 0,
            'fecha_acreditacion' => now()->toDateString(),
            'raw' => ['simulado' => true],
        ]);
    }

    public function refrescar(PagoPasarela $pago): PagoPasarela
    {
        return $pago;
    }

    public function cancelar(PagoPasarela $pago): void
    {
        if ($pago->estado === 'pendiente') {
            $pago->update(['estado' => 'cancelado']);
        }
    }

    public function procesarWebhook(array $payload): ?PagoPasarela
    {
        $ref = $payload['external_reference'] ?? null;

        return $ref ? PagoPasarela::where('external_ref', $ref)->first() : null;
    }
}
