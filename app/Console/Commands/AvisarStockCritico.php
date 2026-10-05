<?php

namespace App\Console\Commands;

use App\Events\StockBajo;
use App\Models\Producto;
use Illuminate\Console\Command;

/**
 * Aviso diario de los productos que cayeron por debajo del mínimo.
 *
 * El evento StockBajo ya existía, pero solo se disparaba en el momento exacto de
 * una venta. Si nadie estaba mirando en ese instante, el aviso se perdía.
 */
class AvisarStockCritico extends Command
{
    protected $signature = 'stock:avisar-critico';

    protected $description = 'Notifica los productos con stock por debajo del mínimo.';

    public function handle(): int
    {
        $criticos = Producto::where('estado', 'activo')
            ->whereColumn('stock', '<=', 'stock_minimo')
            ->get();

        foreach ($criticos as $producto) {
            StockBajo::dispatch($producto);
        }

        $this->info("Productos en stock crítico: {$criticos->count()}.");

        return self::SUCCESS;
    }
}
