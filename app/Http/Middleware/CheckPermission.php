<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

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

        $rol = $user->role()->with('permissions')->first();

        if (! $rol) {
            abort(403, 'Tu cuenta no tiene un rol asignado. Contactá al administrador.');
        }

        if ($rol->nombre === \App\Models\Role::ADMINISTRADOR) {
            return $next($request);
        }

        $clavesDelRol = $rol->permissions->pluck('clave')->toArray();

        foreach ($permisos as $permiso) {
            if (in_array(trim($permiso), $clavesDelRol, true)) {
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
