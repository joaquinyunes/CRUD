<?php

namespace App\Services;

use App\Models\Compra;
use App\Models\Venta;

/**
 * Aplica y revierte el impacto de stock de una venta o compra.
 * Es idempotente: usa la bandera `stock_aplicado` del documento para no
 * duplicar ni dejar movimientos colgados al editar/anular.
 */
class StockDocumentoService
{
    public function __construct(
        private StockService $stock,
        private LoteService $lotes,
    ) {}

    public function aplicarVenta(Venta $venta): void
    {
        if ($venta->stock_aplicado) {
            return;
        }

        $venta->load('detalles.producto');

        foreach ($venta->detalles as $detalle) {
            $this->stock->registrarSalida($detalle->producto, $detalle->cantidad, 'venta', $venta->id, $venta->deposito_id);

            if ($this->lotes->controla($detalle->producto)) {
                $this->lotes->consumirFEFO($detalle->producto_id, $venta->deposito_id, (float) $detalle->cantidad);
            }
        }

        $venta->forceFill(['stock_aplicado' => true])->saveQuietly();
    }

    public function revertirVenta(Venta $venta): void
    {
        if (! $venta->stock_aplicado) {
            return;
        }

        $venta->load('detalles.producto');

        foreach ($venta->detalles as $detalle) {
            $this->stock->registrarDevolucion($detalle->producto, $detalle->cantidad, 'venta', $venta->id, $venta->deposito_id);

            if ($this->lotes->controla($detalle->producto)) {
                $this->lotes->devolver($detalle->producto_id, $venta->deposito_id, (float) $detalle->cantidad);
            }
        }

        $venta->forceFill(['stock_aplicado' => false])->saveQuietly();
    }

    public function aplicarCompra(Compra $compra): void
    {
        if ($compra->stock_aplicado) {
            return;
        }

        $compra->load('detalles.producto');

        foreach ($compra->detalles as $detalle) {
            $this->stock->registrarEntrada($detalle->producto, $detalle->cantidad, 'compra', $compra->id, $compra->deposito_id);
        }

        $compra->forceFill(['stock_aplicado' => true])->saveQuietly();
    }

    public function revertirCompra(Compra $compra): void
    {
        if (! $compra->stock_aplicado) {
            return;
        }

        $compra->load('detalles.producto');

        foreach ($compra->detalles as $detalle) {
            $this->stock->registrarSalida($detalle->producto, $detalle->cantidad, 'compra', $compra->id, $compra->deposito_id);
        }

        $compra->forceFill(['stock_aplicado' => false])->saveQuietly();
    }
}
