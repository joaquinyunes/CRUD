<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Desactivar a alguien tiene que echarlo del sistema ya, no en su próximo
 * login: si sigue con la sesión abierta, la baja no sirvió de nada.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && ! $user->activo) {
            if ($request->expectsJson()) {
                $user->currentAccessToken()->delete();

                return response()->json(['error' => 'Esta cuenta está desactivada.'], 403);
            }

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Esta cuenta está desactivada. Hablá con un administrador.']);
        }

        return $next($request);
    }
}
