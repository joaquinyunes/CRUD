<?php

namespace App\Http\Middleware;

use App\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deja pasar si el rol del usuario tiene alguno de los permisos pedidos.
 *
 * No hay nombres de rol escritos en el código: "poder todo" es el permiso
 * comodín "*", como cualquier otro. Así se puede crear un segundo rol con
 * acceso total, o sacarle una parte al administrador, sin tocar una línea.
 */
class CheckPermission
{
    public function handle(Request $request, Closure $next, string ...$permisos): Response
    {
        $user = Auth::user();

        if (! $user) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'No autenticado.'], 401);
            }

            return redirect()->route('login');
        }

        if (! $user->role_id) {
            abort(403, 'Tu cuenta no tiene un rol asignado. Contactá al administrador.');
        }

        $claves = Role::clavesDe($user->role_id);

        if (in_array(Role::TODO, $claves, true)) {
            return $next($request);
        }

        foreach ($permisos as $permiso) {
            if (in_array(trim($permiso), $claves, true)) {
                return $next($request);
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'error'    => 'Acceso denegado.',
                'mensaje'  => 'No tenés permiso para realizar esta acción.',
                'permisos' => $permisos,
            ], 403);
        }

        abort(403, 'No tenés permiso para acceder a esta sección.');
    }
}
