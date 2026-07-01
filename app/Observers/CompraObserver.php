<?php

namespace App\Observers;

use App\Models\Compra;
use App\Services\StockService;

class CompraObserver
{
    public function __construct(
        private StockService $stockService
    ) {}

    public function created(Compra $compra): void
    {
        if ($compra->estado === 'completada') {
            $this->agregarStock($compra);
        }
    }

    public function updated(Compra $compra): void
    {
        if ($compra->wasChanged('estado')) {
            if ($compra->estado === 'completada' && $compra->getOriginal('estado') !== 'completada') {
                $this->agregarStock($compra);
            } elseif ($compra->estado === 'cancelada' && $compra->getOriginal('estado') === 'completada') {
                $this->descontarStock($compra);
            }
        }
    }

    public function deleted(Compra $compra): void
    {
        if ($compra->estado === 'completada') {
            $this->descontarStock($compra);
        }
    }

    private function agregarStock(Compra $compra): void
    {
        $compra->load('detalles.producto');

        foreach ($compra->detalles as $detalle) {
            $this->stockService->registrarEntrada(
                $detalle->producto,
                $detalle->cantidad,
                'compra',
                $compra->id
            );
        }
    }

    private function descontarStock(Compra $compra): void
    {
        $compra->load('detalles.producto');

        foreach ($compra->detalles as $detalle) {
            $this->stockService->registrarSalida(
                $detalle->producto,
                $detalle->cantidad,
                'compra',
                $compra->id
            );
        }
    }
}
