<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProveedorRequest;
use App\Models\Proveedor;
use App\Support\Orden;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProveedorController extends Controller
{
    public function index(Request $request): View
    {
        // Los archivados solo se ven pidiéndolos; no estorban el día a día.
        $query = Proveedor::query();

        if ($request->boolean('archivados')) {
            $query->where('estado', 'archivado');
        } else {
            $query->where('estado', 'activo');
        }

        if ($request->filled('buscar')) {
            $query->buscar($request->buscar);
        }

        $proveedores = Orden::aplicar($query, [
            'nombre'    => 'nombre',
            'cuit'      => 'cuit',
            'telefono'  => 'telefono',
            'email'     => 'email',
            'direccion' => 'direccion',
        ], 'nombre')->paginate(20)->withQueryString();

        return view('proveedores.index', compact('proveedores'));
    }

    public function create(): View
    {
        return view('proveedores.form');
    }

    public function store(ProveedorRequest $request): RedirectResponse
    {
        Proveedor::create($request->validated());

        return redirect()->route('proveedores.index')
            ->with('success', 'Proveedor creado correctamente.');
    }

    public function edit(Proveedor $proveedor): View
    {
        return view('proveedores.form', compact('proveedor'));
    }

    public function update(ProveedorRequest $request, Proveedor $proveedor): RedirectResponse
    {
        $proveedor->update($request->validated());

        return redirect()->route('proveedores.index')
            ->with('success', 'Proveedor actualizado correctamente.');
    }

    /**
     * Archiva en vez de borrar. Un proveedor con compras encima no se puede
     * borrar sin perder el historial: la clave foránea lo impide, y antes eso
     * llegaba al usuario como un error de base de datos.
     */
    public function destroy(Proveedor $proveedor): RedirectResponse
    {
        $compras = $proveedor->compras()->count();

        if ($compras > 0) {
            $proveedor->update(['estado' => 'archivado']);

            return redirect()->route('proveedores.index')->with(
                'success',
                "Proveedor \"{$proveedor->nombre}\" archivado: tiene {$compras} ".
                ($compras === 1 ? 'compra' : 'compras').' en el historial, así que se conserva.'
            );
        }

        $proveedor->delete();

        return redirect()->route('proveedores.index')
            ->with('success', 'Proveedor eliminado correctamente.');
    }

    public function restaurar(Proveedor $proveedor): RedirectResponse
    {
        $proveedor->update(['estado' => 'activo']);

        return redirect()->route('proveedores.index', ['archivados' => 1])
            ->with('success', "Proveedor \"{$proveedor->nombre}\" restaurado.");
    }
}
