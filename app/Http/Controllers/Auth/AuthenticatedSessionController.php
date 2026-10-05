<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // Con doble factor activo la contraseña sola no abre sesión: se cierra
        // lo que authenticate() dejó iniciado y queda esperando el código, con
        // nada más que el id del usuario guardado en la sesión.
        if ($request->user()->tieneDobleFactor()) {
            $usuarioId = $request->user()->id;

            Auth::guard('web')->logout();
            $request->session()->put(DesafioDobleFactorController::SESION, $usuarioId);
            $request->session()->put(DesafioDobleFactorController::SESION_RECORDAR, $request->boolean('remember'));

            return redirect()->route('doble-factor.desafio');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
