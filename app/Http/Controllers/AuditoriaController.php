<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\User;
use App\Support\Orden;
use Illuminate\Http\Request;

class AuditoriaController extends Controller
{
    public function index(Request $request)
    {
        $buscar = $request->input('buscar');
        $modelo = $request->input('modelo');
        $usuarioId = $request->input('usuario_id');
        $desde = $request->input('desde');
        $hasta = $request->input('hasta');

        $query = Auditoria::with('user')
            ->paraBuscar($buscar)
            ->paraModelo($modelo)
            ->paraUsuario($usuarioId)
            ->when($desde, fn ($q) => $q->whereDate('created_at', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('created_at', '<=', $hasta));

        $registros = Orden::aplicar($query, [
            'fecha'    => fn ($q, $dir) => $q->orderBy('created_at', $dir)->orderBy('id', $dir),
            'usuario'  => User::select('name')->whereColumn('users.id', 'auditoria.user_id'),
            'ip'       => 'ip',
            'accion'   => 'accion',
            'modelo'   => 'modelo_afectado',
            'registro' => 'modelo_id',
        ], 'fecha', 'desc')
            ->paginate(50)
            ->withQueryString();

        $modelos = Auditoria::query()
            ->select('modelo_afectado')
            ->distinct()
            ->whereNotNull('modelo_afectado')
            ->orderBy('modelo_afectado')
            ->pluck('modelo_afectado');

        $usuarios = User::orderBy('name')->get(['id', 'name']);

        return view('auditoria.index', compact(
            'registros',
            'modelos',
            'usuarios',
            'buscar',
            'modelo',
            'usuarioId',
            'desde',
            'hasta',
        ));
    }
}
