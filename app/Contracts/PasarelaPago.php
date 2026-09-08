<?php

namespace App\Contracts;

use App\Models\PagoPasarela;

interface PasarelaPago
{
    /** Crea una intención de cobro (QR dinámico) y devuelve el PagoPasarela pendiente. */
    public function crearCobro(float $monto, string $referencia): PagoPasarela;

    /** Consulta el estado real en la pasarela y actualiza el PagoPasarela. */
    public function refrescar(PagoPasarela $pago): PagoPasarela;

    /** Cancela una intención de cobro pendiente. */
    public function cancelar(PagoPasarela $pago): void;

    /**
     * Procesa una notificación webhook; devuelve el PagoPasarela afectado o null.
     *
     * @param  array<string,mixed>  $payload
     */
    public function procesarWebhook(array $payload): ?PagoPasarela;

    public function disponible(): bool;
}
