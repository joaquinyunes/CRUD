<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProveedorController extends Controller
{
    public function index(Request $request): View
    {
        $query = Proveedor::query();

        if ($request->filled('buscar')) {
            $query->buscar($request->buscar);
        }

        $proveedores = $query->orderBy('nombre')->paginate(20)->withQueryString();

        return view('proveedores.index', compact('proveedores'));
    }

    public function create(): View
    {
        return view('proveedores.form');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre'    => ['required', 'string', 'max:255'],
            'cuit'      => ['nullable', 'string', 'max:20'],
            'telefono'  => ['nullable', 'string', 'max:50'],
            'email'     => ['nullable', 'email', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
        ]);

        Proveedor::create($validated);

        return redirect()->route('proveedores.index')
                         ->with('success', 'Proveedor creado correctamente.');
    }

    public function edit(Proveedor $proveedor): View
    {
        return view('proveedores.form', compact('proveedor'));
    }

    public function update(Request $request, Proveedor $proveedor): RedirectResponse
    {
        $validated = $request->validate([
            'nombre'    => ['required', 'string', 'max:255'],
            'cuit'      => ['nullable', 'string', 'max:20'],
            'telefono'  => ['nullable', 'string', 'max:50'],
            'email'     => ['nullable', 'email', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
        ]);

        $proveedor->update($validated);

        return redirect()->route('proveedores.index')
                         ->with('success', 'Proveedor actualizado correctamente.');
    }

    public function destroy(Proveedor $proveedor): RedirectResponse
    {
        $proveedor->delete();

        return redirect()->route('proveedores.index')
                         ->with('success', 'Proveedor eliminado correctamente.');
    }
}
