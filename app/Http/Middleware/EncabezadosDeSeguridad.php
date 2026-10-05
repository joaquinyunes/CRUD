<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Encabezados que le piden al navegador que no ayude a un atacante.
 *
 * No hay Content-Security-Policy a propósito: las pantallas usan scripts y
 * estilos en línea, y una política que no los permita rompería la interfaz,
 * mientras que una que los permita (`unsafe-inline`) no protege de lo que más
 * interesa. Cuando esos bloques salgan a archivos propios, es lo que sigue.
 */
class EncabezadosDeSeguridad
{
    public function handle(Request $request, Closure $next): Response
    {
        $respuesta = $next($request);

        $respuesta->headers->set('X-Content-Type-Options', 'nosniff');
        // Nadie tiene por qué meter este sistema en un <iframe> ajeno (clickjacking).
        $respuesta->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $respuesta->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $respuesta->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        // Solo por HTTPS tiene sentido (y es peligroso mandarlo por HTTP plano de
        // desarrollo, donde el navegador recordaría de no volver a usarlo).
        if ($request->isSecure() && app()->isProduction()) {
            $respuesta->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $respuesta;
    }
}
