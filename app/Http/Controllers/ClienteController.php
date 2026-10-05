<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClienteRequest;
use App\Models\Cliente;
use App\Support\Orden;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClienteController extends Controller
{
    public function index(Request $request): View
    {
        $query = Cliente::query()
            ->buscar($request->input('buscar'));

        $estado = $request->input('estado', 'activo');

        if ($estado !== 'todos') {
            $query->where('estado', $estado);
        }

        $clientes = Orden::aplicar($query, [
            'nombre'    => fn ($q, $dir) => $q->orderBy('apellido', $dir)->orderBy('nombre', $dir),
            'documento' => 'documento',
            'email'     => 'email',
            'telefono'  => 'telefono',
            'estado'    => 'estado',
            'creado'    => 'created_at',
        ], 'nombre')
            ->paginate(20)
            ->withQueryString();

        return view('clientes.index', [
            'clientes' => $clientes,
            'buscar'   => $request->input('buscar'),
            'estado'   => $estado,
        ]);
    }

    public function create(): View
    {
        return view('clientes.form', [
            'cliente' => new Cliente,
        ]);
    }

    public function store(ClienteRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        Cliente::create($datos);

        return redirect()
            ->route('clientes.index')
            ->with('success', 'Cliente creado correctamente.');
    }

    public function edit(Cliente $cliente): View
    {
        return view('clientes.form', [
            'cliente' => $cliente,
        ]);
    }

    public function update(ClienteRequest $request, Cliente $cliente): RedirectResponse
    {
        $datos = $request->validated();

        $cliente->update($datos);

        return redirect()
            ->route('clientes.index')
            ->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Cliente $cliente): RedirectResponse
    {
        $cliente->update(['estado' => 'archivado']);

        return redirect()
            ->route('clientes.index')
            ->with('success', 'Cliente archivado correctamente.');
    }
}
