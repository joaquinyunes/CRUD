<?php

namespace App\Providers;

use App\Services\StockService;
use App\Support\Mongo\VerificadorDePresencia;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(StockService::class);

        // exists:/unique: con ids numéricos sobre MongoDB (ver VerificadorDePresencia).
        $this->app->extend('validation.presence', fn ($verificador, $app) => new VerificadorDePresencia($app['db']));
    }

    public function boot(): void
    {
        // Un token de API no debería poder martillar el sistema. El límite va
        // por usuario cuando hay token, y por IP en las rutas públicas.
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)
            ->by($request->user()?->id ?: $request->ip()));
    }
}
