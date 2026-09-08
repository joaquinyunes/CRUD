<?php

namespace App\Services;

use App\Contracts\PasarelaPago;
use App\Models\PagoPasarela;
use App\Models\Venta;

class PasarelaService
{
    public function __construct(private PasarelaPago $pasarela) {}

    public function crearCobro(float $monto, string $referencia): PagoPasarela
    {
        return $this->pasarela->crearCobro($monto, $referencia);
    }

    public function estado(PagoPasarela $pago): PagoPasarela
    {
        return $pago->estado === 'pendiente' ? $this->pasarela->refrescar($pago) : $pago;
    }

    public function cancelar(PagoPasarela $pago): void
    {
        $this->pasarela->cancelar($pago);
    }

    public function webhook(array $payload): ?PagoPasarela
    {
        return $this->pasarela->procesarWebhook($payload);
    }

    /** Asocia los cobros QR de una referencia a la venta ya registrada. */
    public function vincularAVenta(?string $referencia, Venta $venta): void
    {
        if (! $referencia) {
            return;
        }

        PagoPasarela::where('external_ref', $referencia)
            ->whereNull('venta_id')
            ->update(['venta_id' => $venta->id]);
    }
}
