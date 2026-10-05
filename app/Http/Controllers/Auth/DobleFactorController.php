<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\DobleFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Alta y baja del doble factor desde el perfil del propio usuario.
 *
 * Nadie se lo activa ni se lo desactiva a otro: es su cuenta y su teléfono.
 */
class DobleFactorController extends Controller
{
    public function __construct(private DobleFactorService $dobleFactor) {}

    /**
     * Genera el secreto y muestra el QR. Todavía no queda activo: hace falta
     * confirmar con un código para demostrar que la aplicación quedó bien
     * configurada.
     */
    public function iniciar(Request $request): RedirectResponse
    {
        $codigos = $this->dobleFactor->iniciarAlta($request->user());

        return back()->with('codigos_recuperacion', $codigos);
    }

    public function confirmar(Request $request): RedirectResponse
    {
        $request->validate([
            'codigo' => ['required', 'string'],
        ], [
            'codigo.required' => 'Escribí el código de seis dígitos que muestra tu aplicación.',
        ]);

        if (! $this->dobleFactor->confirmar($request->user(), $request->input('codigo'))) {
            return back()->withErrors([
                'codigo' => 'Ese código no es válido. Revisá que la hora del teléfono esté en automático.',
            ]);
        }

        return back()->with('success', 'Doble factor activado. Guardá los códigos de recuperación.');
    }

    /** Desactivar exige la contraseña: un equipo abierto no alcanza. */
    public function desactivar(Request $request): RedirectResponse
    {
        $request->validateWithBag('dobleFactor', [
            'password' => ['required', 'current_password'],
        ]);

        $this->dobleFactor->desactivar($request->user());

        return back()->with('success', 'Doble factor desactivado.');
    }

    public function regenerarCodigos(Request $request): RedirectResponse
    {
        $codigos = $this->dobleFactor->regenerarCodigosDeRecuperacion($request->user());

        return back()->with('codigos_recuperacion', $codigos);
    }
}
