<?php

namespace App\Services\Pasarela;

use App\Contracts\PasarelaPago;
use App\Models\PagoPasarela;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Cobros con QR dinámico de Mercado Pago (API "Instore" / Órdenes QR + Payments).
 * Necesita MP_ACCESS_TOKEN y la caja (POS) dada de alta en la cuenta.
 */
class MercadoPago implements PasarelaPago
{
    private array $cfg;

    public function __construct()
    {
        $this->cfg = config('mercadopago');
    }

    public function disponible(): bool
    {
        return ! empty($this->cfg['access_token']) && ! empty($this->cfg['user_id']);
    }

    private function http()
    {
        return Http::withToken($this->cfg['access_token'])
            ->baseUrl($this->cfg['base_url'])
            ->acceptJson();
    }

    public function crearCobro(float $monto, string $referencia): PagoPasarela
    {
        $this->guard();

        $userId = $this->cfg['user_id'];
        $pos = $this->cfg['external_pos_id'];

        // Crea/actualiza la orden QR asociada a la caja.
        $resp = $this->http()->put("/instore/orders/qr/seller/collectors/{$userId}/pos/{$pos}/qrs", [
            'external_reference' => $referencia,
            'title' => "Venta {$referencia}",
            'notification_url' => route('webhooks.mercadopago'),
            'total_amount' => round($monto, 2),
            'items' => [[
                'title' => 'Venta mostrador',
                'unit_price' => round($monto, 2),
                'quantity' => 1,
                'unit_measure' => 'unit',
                'total_amount' => round($monto, 2),
            ]],
        ]);

        if ($resp->failed()) {
            throw new RuntimeException('Mercado Pago rechazó la creación del cobro: '.$resp->body());
        }

        return PagoPasarela::create([
            'pasarela' => 'mercadopago',
            'external_ref' => $referencia,
            'monto' => round($monto, 2),
            'estado' => 'pendiente',
            'qr_data' => $resp->json('qr_data'),
            'raw' => $resp->json(),
        ]);
    }

    public function refrescar(PagoPasarela $pago): PagoPasarela
    {
        $this->guard();

        $resp = $this->http()->get('/v1/payments/search', [
            'external_reference' => $pago->external_ref,
            'sort' => 'date_created',
            'criteria' => 'desc',
        ]);

        $pay = $resp->json('results.0');
        if ($pay) {
            $this->aplicarPago($pago, $pay);
        }

        return $pago->fresh();
    }

    public function cancelar(PagoPasarela $pago): void
    {
        $this->guard();
        $userId = $this->cfg['user_id'];
        $pos = $this->cfg['external_pos_id'];

        $this->http()->delete("/instore/qr/seller/collectors/{$userId}/pos/{$pos}/orders");
        if ($pago->estado === 'pendiente') {
            $pago->update(['estado' => 'cancelado']);
        }
    }

    public function procesarWebhook(array $payload): ?PagoPasarela
    {
        $paymentId = $payload['data']['id'] ?? ($payload['id'] ?? null);
        if (! $paymentId || ($payload['type'] ?? $payload['topic'] ?? null) !== 'payment') {
            return null;
        }

        $pay = $this->http()->get("/v1/payments/{$paymentId}")->json();
        $ref = $pay['external_reference'] ?? null;
        if (! $ref) {
            return null;
        }

        $pago = PagoPasarela::where('external_ref', $ref)->first();
        if ($pago) {
            $this->aplicarPago($pago, $pay);
        }

        return $pago?->fresh();
    }

    /** @param  array<string,mixed>  $pay */
    private function aplicarPago(PagoPasarela $pago, array $pay): void
    {
        $map = [
            'approved' => 'aprobado',
            'authorized' => 'aprobado',
            'rejected' => 'rechazado',
            'cancelled' => 'cancelado',
            'refunded' => 'cancelado',
        ];

        $detalle = $pay['transaction_details'] ?? [];

        $pago->update([
            'external_id' => $pay['id'] ?? $pago->external_id,
            'estado' => $map[$pay['status'] ?? ''] ?? 'pendiente',
            'neto_acreditado' => $detalle['net_received_amount'] ?? null,
            'comision' => isset($detalle['total_paid_amount'], $detalle['net_received_amount'])
                ? round($detalle['total_paid_amount'] - $detalle['net_received_amount'], 2)
                : null,
            'fecha_acreditacion' => isset($pay['money_release_date'])
                ? substr($pay['money_release_date'], 0, 10) : null,
            'raw' => $pay,
        ]);
    }

    private function guard(): void
    {
        if (! $this->disponible()) {
            throw new RuntimeException('Mercado Pago no está configurado (MP_ACCESS_TOKEN, MP_USER_ID).');
        }
    }
}
