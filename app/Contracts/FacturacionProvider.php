<?php

namespace App\Contracts;

use App\Models\Devolucion;
use App\Models\Venta;

/**
 * Puerta de entrada a un proveedor de facturación electrónica certificado (ARCA).
 *
 * El sistema NUNCA calcula ni escribe un CAE a mano: lo devuelve el proveedor. Mientras
 * no haya proveedor elegido y contador matriculado, rige FacturacionInterna, que deja
 * claro en el comprobante que NO es una factura fiscal.
 *
 * Un proveedor HTTP real debe pedir el CAE ANTES de abrir la transacción de la venta
 * (una llamada de red adentro de una transacción de MongoDB la alarga y la puede abortar)
 * y guardar el resultado en la venta; ante un timeout debe consultar antes de reintentar,
 * para no emitir dos facturas por la misma venta.
 */
interface FacturacionProvider
{
    /** @return array{fiscal: bool, cae: string|null, cae_vencimiento: string|null, numero: string, proveedor: string} */
    public function emitir(Venta $venta): array;

    /** @return array{fiscal: bool, cae: string|null, cae_vencimiento: string|null, numero: string, proveedor: string} */
    public function notaCredito(Devolucion $devolucion): array;
}
