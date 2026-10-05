<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfiguracionRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ConfiguracionController extends Controller
{
    /** Campo del formulario => grupo en que se guarda. */
    private const TEXTOS = [
        'empresa_nombre'          => 'empresa',
        'empresa_ruc'             => 'empresa',
        'empresa_direccion'       => 'empresa',
        'empresa_telefono'        => 'empresa',
        'empresa_email'           => 'empresa',
        'empresa_logo'            => 'empresa',
        'sistema_moneda'          => 'sistema',
        'sistema_simbolo_moneda'  => 'sistema',
        'sistema_iva'             => 'sistema',
        'ventas_prefijo_numero'   => 'ventas',
        'ventas_cantidad_digitos' => 'ventas',
        'ventas_limite_descuento' => 'ventas',
    ];

    /**
     * Las casillas. Una casilla destildada no viaja en el formulario, así que
     * "no vino" tiene que leerse como "apagada": con el criterio de los textos
     * (guardar solo lo que llega) un descuento habilitado no se podía apagar.
     */
    private const CASILLAS = [
        'sistema_impuesto_habilitado' => 'sistema',
        'ventas_permite_descuento'    => 'ventas',
    ];

    public function index(): View
    {
        $configuracion = [
            'empresa' => Setting::obtenerGrupo('empresa'),
            'sistema' => Setting::obtenerGrupo('sistema'),
            'ventas'  => Setting::obtenerGrupo('ventas'),
        ];

        return view('configuracion.index', compact('configuracion'));
    }

    public function update(ConfiguracionRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        foreach (self::TEXTOS as $campo => $grupo) {
            if (array_key_exists($campo, $datos) && $datos[$campo] !== null) {
                Setting::establecer($campo, (string) $datos[$campo], $grupo);
            }
        }

        foreach (self::CASILLAS as $campo => $grupo) {
            Setting::establecer($campo, $request->boolean($campo) ? '1' : '0', $grupo);
        }

        return redirect()->route('configuracion.index')->with('success', 'Configuración actualizada correctamente.');
    }
}
