<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Encabezados que le piden al navegador que no ayude a un atacante.
 *
 * La Content-Security-Policy completa va en modo SOLO REPORTE: las pantallas usan
 * scripts y estilos en línea, así que `unsafe-inline` sigue permitido y la
 * política todavía no frena inyecciones de script. Lo que sí delimita ya es de
 * dónde se cargan scripts, estilos, imágenes y conexiones. Para activarla, cambiar el nombre del encabezado a Content-Security-Policy;
 * el paso siguiente es sacar los
 * bloques en línea a archivos propios y quitar `unsafe-inline`.
 * Aparte va un CSP aplicado solo con `frame-ancestors` (equivale a X-Frame-Options).
 */
class EncabezadosDeSeguridad
{
    // cdn.jsdelivr.net: Chart.js (dashboard) y FullCalendar (calendario).
    // 'unsafe-eval': Alpine.js evalúa sus expresiones x-data con new Function;
    // se quita migrando al build @alpinejs/csp.
    private const CSP_REPORT_ONLY = "default-src 'self'; "
        ."script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net; "
        ."style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; "
        ."img-src 'self' data: blob:; font-src 'self' data:; connect-src 'self'; "
        ."object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'";

    // Lo que se puede aplicar ya sin romper pantallas: sin plugins, sin <base> ajeno,
    // formularios solo hacia el propio dominio y sin <iframe> ajeno. script-src sigue
    // en solo reporte hasta sacar los bloques en línea (ver docs/SEGURIDAD.md, S2).
    private const CSP_APLICADA = "object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'";

    public function handle(Request $request, Closure $next): Response
    {
        $respuesta = $next($request);

        $respuesta->headers->set('X-Content-Type-Options', 'nosniff');
        // Nadie tiene por qué meter este sistema en un <iframe> ajeno (clickjacking).
        $respuesta->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $respuesta->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $respuesta->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        $respuesta->headers->set('Content-Security-Policy', self::CSP_APLICADA);
        $respuesta->headers->set('Content-Security-Policy-Report-Only', self::CSP_REPORT_ONLY);

        // Solo por HTTPS tiene sentido (y es peligroso mandarlo por HTTP plano de
        // desarrollo, donde el navegador recordaría de no volver a usarlo).
        if ($request->isSecure() && app()->isProduction()) {
            $respuesta->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $respuesta;
    }
}
