<?php

namespace App\Providers;

use App\Models\Compra;
use App\Models\Venta;
use App\Observers\CompraObserver;
use App\Observers\VentaObserver;
use App\Services\StockService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(StockService::class);
    }

    public function boot(): void
    {
        Venta::observe(VentaObserver::class);
        Compra::observe(CompraObserver::class);
    }
}
