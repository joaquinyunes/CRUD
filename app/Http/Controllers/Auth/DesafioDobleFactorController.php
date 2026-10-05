<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DobleFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * El segundo paso del login.
 *
 * La contraseña ya se verificó, pero el usuario todavía no está autenticado:
 * queda en la sesión únicamente su id, y recién al validar el código se abre la
 * sesión de verdad. Así, saber la contraseña no alcanza para entrar.
 */
class DesafioDobleFactorController extends Controller
{
    /**
     * Claves de sesión donde espera el usuario a medio autenticar.
     *
     * Son dos claves hermanas y no una anidada: la sesión de Laravel interpreta
     * el punto como anidamiento, así que guardar "x" y después "x.y" convierte
     * lo primero en un array y se pierde el id.
     */
    public const SESION = 'login_doble_factor_id';

    public const SESION_RECORDAR = 'login_doble_factor_recordar';

    private const INTENTOS = 5;

    public function __construct(private DobleFactorService $dobleFactor) {}

    public function create(Request $request): View|RedirectResponse
    {
        if (! $this->usuarioPendiente($request)) {
            return redirect()->route('login');
        }

        return view('auth.doble-factor');
    }

    public function store(Request $request): RedirectResponse
    {
        $usuario = $this->usuarioPendiente($request);

        if (! $usuario) {
            return redirect()->route('login');
        }

        $request->validate(['codigo' => ['required', 'string']]);

        // Un código de seis dígitos se adivina por fuerza bruta en minutos si
        // se puede probar sin límite.
        $clave = 'doble-factor:'.$usuario->id;

        if (RateLimiter::tooManyAttempts($clave, self::INTENTOS)) {
            throw ValidationException::withMessages([
                'codigo' => 'Demasiados intentos. Probá de nuevo en '.
                    RateLimiter::availableIn($clave).' segundos.',
            ]);
        }

        if (! $this->dobleFactor->verificar($usuario, $request->input('codigo'))) {
            RateLimiter::hit($clave, 300);

            throw ValidationException::withMessages([
                'codigo' => 'Código incorrecto.',
            ]);
        }

        RateLimiter::clear($clave);

        $recordar = (bool) $request->session()->pull(self::SESION_RECORDAR, false);
        $request->session()->forget(self::SESION);

        Auth::login($usuario, $recordar);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /** Cancela el login a medias y vuelve al formulario. */
    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget([self::SESION, self::SESION_RECORDAR]);

        return redirect()->route('login');
    }

    private function usuarioPendiente(Request $request): ?User
    {
        $id = $request->session()->get(self::SESION);

        if (! $id) {
            return null;
        }

        $usuario = User::find($id);

        // Si lo dieron de baja entre el primer paso y el segundo, no entra.
        return $usuario && $usuario->activo ? $usuario : null;
    }
}
