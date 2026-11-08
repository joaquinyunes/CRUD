<?php

namespace App\Http\Middleware;

use App\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Quien puede todo (rol con el permiso comodín) no opera sin doble factor.
 *
 * Mientras no lo active, lo único que puede abrir es su perfil, donde lo da de
 * alta. Se enciende solo en producción (config `sistema.doble_factor_admin_obligatorio`).
 */
class ExigirDobleFactorAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if (
            $usuario
            && config('sistema.doble_factor_admin_obligatorio')
            && ! $usuario->tieneDobleFactor()
            && $usuario->role?->tienePermiso(Role::TODO)
            && ! $request->routeIs('profile.*', 'doble-factor.*', 'logout', 'password.*', 'verification.*')
        ) {
            return redirect()->route('profile.edit')
                ->with('error', 'Como administrador tenés que activar el doble factor antes de seguir.');
        }

        return $next($request);
    }
}
