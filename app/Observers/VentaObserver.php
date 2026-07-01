<?php

namespace App\Observers;

use App\Models\Venta;
use App\Services\StockService;

class VentaObserver
{
    public function __construct(
        private StockService $stockService
    ) {}

    public function created(Venta $venta): void
    {
        if ($venta->estado === 'completada') {
            $this->descontarStock($venta);
        }
    }

    public function updated(Venta $venta): void
    {
        if ($venta->wasChanged('estado')) {
            if ($venta->estado === 'completada' && $venta->getOriginal('estado') !== 'completada') {
                $this->descontarStock($venta);
            } elseif ($venta->estado === 'cancelada' && $venta->getOriginal('estado') === 'completada') {
                $this->devolverStock($venta);
            }
        }
    }

    public function deleted(Venta $venta): void
    {
        if ($venta->estado === 'completada') {
            $this->devolverStock($venta);
        }
    }

    private function descontarStock(Venta $venta): void
    {
        $venta->load('detalles.producto');

        foreach ($venta->detalles as $detalle) {
            $this->stockService->registrarSalida(
                $detalle->producto,
                $detalle->cantidad,
                'venta',
                $venta->id
            );
        }
    }

    private function devolverStock(Venta $venta): void
    {
        $venta->load('detalles.producto');

        foreach ($venta->detalles as $detalle) {
            $this->stockService->registrarDevolucion(
                $detalle->producto,
                $detalle->cantidad,
                'venta',
                $venta->id
            );
        }
    }
}
