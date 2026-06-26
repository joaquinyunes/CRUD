<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoriaController extends Controller
{
    public function index(Request $request): View
    {
        $query = Categoria::query();

        if ($request->filled('buscar')) {
            $query->where('nombre', 'like', '%' . $request->input('buscar') . '%');
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->input('estado') === 'activo');
        }

        $categorias = $query->orderBy('nombre')->paginate(15)->withQueryString();

        return view('categorias.index', [
            'categorias' => $categorias,
            'buscar' => $request->input('buscar', ''),
            'estadoFiltro' => $request->input('estado', ''),
        ]);
    }

    public function create(): View
    {
        return view('categorias.form', [
            'categoria' => new Categoria(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Categoria::create($this->validarDatos($request));

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

    public function update(Request $request, Categoria $categoria): RedirectResponse
    {
        $categoria->update($this->validarDatos($request));

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

    private function validarDatos(Request $request): array
    {
        $validado = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ]);

        $validado['estado'] = $request->boolean('estado');

        return $validado;
    }
}
