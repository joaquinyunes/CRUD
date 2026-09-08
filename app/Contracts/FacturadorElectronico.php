<?php

namespace App\Contracts;

use App\Models\ComprobanteAfip;
use App\Models\Venta;

interface FacturadorElectronico
{
    /**
     * Autoriza (o simula) el comprobante fiscal de una venta y devuelve
     * el ComprobanteAfip persistido con su CAE.
     */
    public function autorizar(Venta $venta, int $tipoComprobante, int $docTipo, string $docNro): ComprobanteAfip;

    /** Último número autorizado para un punto de venta + tipo (para el correlativo). */
    public function ultimoNumero(int $puntoVenta, int $tipoComprobante): int;

    public function disponible(): bool;
}
