<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class ConfiguracionController extends Controller
{
    public function index()
    {
        $configuracion = [
            'empresa' => Setting::obtenerGrupo('empresa'),
            'sistema' => Setting::obtenerGrupo('sistema'),
            'ventas'  => Setting::obtenerGrupo('ventas'),
        ];

        return view('configuracion.index', compact('configuracion'));
    }

    public function update(Request $request)
    {
        $camposEmpresa = ['empresa_nombre', 'empresa_ruc', 'empresa_direccion', 'empresa_telefono', 'empresa_email', 'empresa_logo'];
        $camposSistema = ['sistema_moneda', 'sistema_simbolo_moneda', 'sistema_iva'];
        $camposVentas  = ['ventas_prefijo_numero', 'ventas_cantidad_digitos'];

        $todos = array_merge($camposEmpresa, $camposSistema, $camposVentas);

        foreach ($todos as $campo) {
            if ($request->has($campo) || $request->filled($campo)) {
                $grupo = (str_starts_with($campo, 'empresa') ? 'empresa'
                       : (str_starts_with($campo, 'ventas') ? 'ventas'
                       : 'sistema'));
                Setting::establecer($campo, $request->input($campo), $grupo);
            }
        }

        return Redirect::route('configuracion.index')->with('success', 'Configuración actualizada correctamente.');
    }
}
