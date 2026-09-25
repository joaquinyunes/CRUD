<?php

namespace App\Services;

use App\Models\Compra;
use App\Models\MetodoPago;
use App\Models\Venta;
use App\Support\CalculadorTotales;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Registra cobros/pagos posteriores sobre ventas y compras y actualiza
 * el estado de pago. Si el método es efectivo e hay caja abierta, impacta caja.
 */
class PagoDocumentoService
{
    public function __construct(private CajaService $caja) {}

    public function pagarVenta(Venta $venta, int $metodoPagoId, float $monto, ?string $referencia = null): void
    {
        $this->registrar($venta, 'venta', $metodoPagoId, $monto, $referencia);
    }

    public function pagarCompra(Compra $compra, int $metodoPagoId, float $monto, ?string $referencia = null): void
    {
        $this->registrar($compra, 'compra', $metodoPagoId, $monto, $referencia);
    }

    private function registrar(Venta|Compra $doc, string $tipo, int $metodoPagoId, float $monto, ?string $referencia): void
    {
        $monto = round($monto, 2);

        if ($monto <= 0) {
            throw new RuntimeException('El monto debe ser mayor a 0.');
        }

        if (in_array($doc->estado, ['anulada', 'cancelada'], true)) {
            throw new RuntimeException('No se puede registrar pagos sobre un documento anulado.');
        }

        if ($monto > $doc->saldoPendiente() + 0.01) {
            throw new RuntimeException('El monto supera el saldo pendiente ($'.number_format($doc->saldoPendiente(), 2).').');
        }

        $metodo = MetodoPago::findOrFail($metodoPagoId);

        DB::transaction(function () use ($doc, $tipo, $metodo, $monto, $referencia) {
            $doc->pagos()->create([
                'metodo_pago_id' => $metodo->id,
                'monto'          => $monto,
                'referencia'     => $referencia,
            ]);

            $pagado = round((float) $doc->pagado + $monto, 2);
            $doc->update([
                'pagado'      => $pagado,
                'estado_pago' => CalculadorTotales::estadoPago((float) $doc->total_final, $pagado),
            ]);

            if ($metodo->codigo === 'efectivo') {
                if ($tipo === 'venta') {
                    $this->caja->registrarMovimiento('ingreso', "Cobro venta {$doc->numero}", $monto, 'venta', $doc->id);
                } else {
                    $this->caja->registrarMovimiento('egreso', "Pago compra {$doc->numero}", $monto, 'compra', $doc->id);
                }
            }
        });
    }
}
