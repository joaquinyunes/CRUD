<?php

namespace App\Providers;

use App\Events\CompraCreada;
use App\Events\StockBajo;
use App\Events\VentaCreada;
use App\Listeners\NotificarCompraCreada;
use App\Listeners\NotificarStockBajo;
use App\Listeners\NotificarVentaCreada;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        VentaCreada::class => [
            NotificarVentaCreada::class,
        ],
        CompraCreada::class => [
            NotificarCompraCreada::class,
        ],
        StockBajo::class => [
            NotificarStockBajo::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}
