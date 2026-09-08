<?php

namespace App\Http\Controllers;

use App\Models\ListaPrecio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ListaPrecioController extends Controller
{
    public function index(): View
    {
        $listas = ListaPrecio::withCount('precios')->orderBy('orden')->orderBy('nombre')->get();

        return view('listas_precio.index', compact('listas'));
    }

    public function store(Request $request): RedirectResponse
    {
        ListaPrecio::create($this->validar($request));

        return back()->with('success', 'Lista de precios creada.');
    }

    public function update(Request $request, ListaPrecio $lista_precio): RedirectResponse
    {
        $lista_precio->update($this->validar($request));

        return back()->with('success', 'Lista actualizada.');
    }

    public function destroy(ListaPrecio $lista_precio): RedirectResponse
    {
        $lista_precio->delete();

        return back()->with('success', 'Lista eliminada.');
    }

    private function validar(Request $request): array
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:80'],
            'ajuste_pct' => ['required', 'numeric', 'between:-90,300'],
            'orden' => ['nullable', 'integer'],
        ]);

        $data['activa'] = $request->boolean('activa');
        $data['orden'] = $data['orden'] ?? 0;

        return $data;
    }
}
