<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
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

        $clientes = $query->orderBy('apellido')
            ->orderBy('nombre')
            ->paginate(20)
            ->withQueryString();

        return view('clientes.index', [
            'clientes' => $clientes,
            'buscar' => $request->input('buscar'),
            'estado' => $estado,
        ]);
    }

    public function create(): View
    {
        return view('clientes.form', [
            'cliente' => new Cliente(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validarDatos($request);

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

    public function update(Request $request, Cliente $cliente): RedirectResponse
    {
        $datos = $this->validarDatos($request);

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

    private function validarDatos(Request $request): array
    {
        return $request->validate([
            'nombre'       => ['required', 'string', 'max:255'],
            'apellido'     => ['required', 'string', 'max:255'],
            'documento'    => ['nullable', 'string', 'max:50'],
            'email'        => ['nullable', 'email', 'max:255'],
            'telefono'     => ['nullable', 'string', 'max:50'],
            'direccion'    => ['nullable', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string'],
            'estado'       => ['required', 'in:activo,archivado,eliminado'],
        ]);
    }
}
