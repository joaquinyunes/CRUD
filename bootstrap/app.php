<?php

use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'permiso' => CheckPermission::class,
        ]);

        // Laravel no limita la API por defecto. Sin esto, /api/login queda
        // abierto a fuerza bruta y cualquier token puede hacer miles de
        // consultas por minuto.
        $middleware->api(prepend: [
            ThrottleRequests::class.':api',
        ]);

        // Una cuenta desactivada deja de servir en el acto, tenga sesión abierta
        // o un token de API ya emitido.
        $middleware->web(append: [EnsureUserIsActive::class]);
        $middleware->api(append: [EnsureUserIsActive::class]);

        // Detras de un balanceador (Render, Fly, Heroku, nginx) la peticion
        // llega por HTTP aunque el visitante haya entrado por HTTPS. Sin esto
        // Laravel genera los enlaces y las redirecciones en http://, y el
        // navegador los bloquea como contenido inseguro.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
