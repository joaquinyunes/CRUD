<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use App\Services\StockService;
use App\Support\Mongo\Conexion;
use App\Support\Mongo\VerificadorDePresencia;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Sanctum\Sanctum;

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
        // /up (el health check de Render y de Docker) solo da 200 si MongoDB contesta.
        Event::listen(DiagnosingHealth::class, fn () => Conexion::mongo()->getDatabase()->command(['ping' => 1]));

        // Contraseñas: 12 o más caracteres en todo el sistema (alta, cambio y reseteo).
        Password::defaults(fn () => Password::min(12));

        // Los tokens de API viven en MongoDB junto con los usuarios.
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        // Un token de API no debería poder martillar el sistema. El límite va
        // por usuario cuando hay token, y por IP en las rutas públicas.
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)
            ->by($request->user()?->id ?: $request->ip()));
    }
}
