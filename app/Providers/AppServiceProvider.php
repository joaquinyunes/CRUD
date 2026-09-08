<?php

namespace App\Providers;

use App\Contracts\FacturadorElectronico;
use App\Services\Afip\NullFacturador;
use App\Services\Afip\WsfeFacturador;
use App\Services\StockService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(StockService::class);

        $this->app->bind(FacturadorElectronico::class, function () {
            return config('afip.driver') === 'wsfe'
                ? new WsfeFacturador
                : new NullFacturador;
        });
    }

    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.rhythm');
        Paginator::defaultSimpleView('vendor.pagination.rhythm-simple');
    }
}
