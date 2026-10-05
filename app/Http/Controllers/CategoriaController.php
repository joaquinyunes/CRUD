<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoriaRequest;
use App\Models\Categoria;
use App\Support\Orden;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoriaController extends Controller
{
    public function index(Request $request): View
    {
        $query = Categoria::query();

        if ($request->filled('buscar')) {
            $query->where('nombre', 'like', '%'.$request->input('buscar').'%');
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->input('estado') === 'activo');
        }

        $categorias = Orden::aplicar($query, [
            'nombre'      => 'nombre',
            'descripcion' => 'descripcion',
            'estado'      => 'estado',
        ], 'nombre')->paginate(15)->withQueryString();

        return view('categorias.index', [
            'categorias'   => $categorias,
            'buscar'       => $request->input('buscar', ''),
            'estadoFiltro' => $request->input('estado', ''),
        ]);
    }

    public function create(): View
    {
        return view('categorias.form', [
            'categoria' => new Categoria,
        ]);
    }

    public function store(CategoriaRequest $request): RedirectResponse
    {
        Categoria::create($request->datosParaGuardar());

        return redirect()
            ->route('categorias.index')
            ->with('success', 'Categoría creada correctamente.');
    }

    public function edit(Categoria $categoria): View
    {
        return view('categorias.form', [
            'categoria' => $categoria,
        ]);
    }

    public function update(CategoriaRequest $request, Categoria $categoria): RedirectResponse
    {
        $categoria->update($request->datosParaGuardar());

        return redirect()
            ->route('categorias.index')
            ->with('success', 'Categoría actualizada correctamente.');
    }

    public function destroy(Categoria $categoria): RedirectResponse
    {
        $categoria->update(['estado' => false]);

        return redirect()
            ->route('categorias.index')
            ->with('success', 'Categoría desactivada correctamente.');
    }
}
