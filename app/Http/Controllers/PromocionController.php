<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Promocion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PromocionController extends Controller
{
    public function index(Request $request): View
    {
        $promociones = Promocion::with('producto', 'categoria')
            ->orderByDesc('activa')->orderByDesc('prioridad')->orderBy('nombre')
            ->paginate(20)->withQueryString();

        return view('promociones.index', compact('promociones'));
    }

    public function create(): View
    {
        return view('promociones.form', [
            'promocion' => new Promocion(['tipo' => 'porcentaje', 'alcance' => 'producto', 'activa' => true]),
            'productos' => Producto::where('estado', 'activo')->orderBy('nombre')->get(['id', 'nombre']),
            'categorias' => Categoria::activas()->orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Promocion::create($this->validar($request));

        return redirect()->route('promociones.index')->with('success', 'Promoción creada.');
    }

    public function edit(Promocion $promocion): View
    {
        return view('promociones.form', [
            'promocion' => $promocion,
            'productos' => Producto::where('estado', 'activo')->orderBy('nombre')->get(['id', 'nombre']),
            'categorias' => Categoria::activas()->orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function update(Request $request, Promocion $promocion): RedirectResponse
    {
        $promocion->update($this->validar($request));

        return redirect()->route('promociones.index')->with('success', 'Promoción actualizada.');
    }

    public function destroy(Promocion $promocion): RedirectResponse
    {
        $promocion->delete();

        return redirect()->route('promociones.index')->with('success', 'Promoción eliminada.');
    }

    private function validar(Request $request): array
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'tipo' => ['required', 'in:porcentaje,monto,precio_fijo,nxm'],
            'valor' => ['nullable', 'numeric', 'min:0', 'required_unless:tipo,nxm'],
            'n' => ['nullable', 'integer', 'min:2', 'required_if:tipo,nxm'],
            'm' => ['nullable', 'integer', 'min:1', 'required_if:tipo,nxm', 'lt:n'],
            'alcance' => ['required', 'in:producto,categoria,todos'],
            'producto_id' => ['nullable', 'exists:productos,id', 'required_if:alcance,producto'],
            'categoria_id' => ['nullable', 'exists:categorias,id', 'required_if:alcance,categoria'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'hora_desde' => ['nullable', 'date_format:H:i'],
            'hora_hasta' => ['nullable', 'date_format:H:i'],
            'dias' => ['nullable', 'array'],
            'dias.*' => ['integer', 'between:1,7'],
            'prioridad' => ['nullable', 'integer'],
        ]);

        $data['activa'] = $request->boolean('activa');
        $data['prioridad'] = $data['prioridad'] ?? 0;

        if ($data['alcance'] !== 'producto') {
            $data['producto_id'] = null;
        }
        if ($data['alcance'] !== 'categoria') {
            $data['categoria_id'] = null;
        }

        return $data;
    }
}
