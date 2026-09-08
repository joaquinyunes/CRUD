<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EtiquetaController extends Controller
{
    public function index(Request $request): View
    {
        $categorias = Categoria::activas()->orderBy('nombre')->get();
        $productos = Producto::where('estado', 'activo')
            ->when($request->filled('categoria_id'), fn ($q) => $q->where('categoria_id', $request->categoria_id))
            ->when($request->filled('buscar'), fn ($q) => $q->where('nombre', 'like', '%'.$request->buscar.'%'))
            ->orderBy('nombre')->limit(300)->get(['id', 'nombre', 'codigo', 'codigo_barra', 'precio_venta']);

        return view('etiquetas.index', compact('categorias', 'productos'));
    }

    public function imprimir(Request $request): View
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'exists:productos,id'],
            'items.*.copias' => ['required', 'integer', 'min:1', 'max:200'],
            'columnas' => ['nullable', 'integer', 'between:2,5'],
        ]);

        $columnas = $data['columnas'] ?? 3;
        $productos = Producto::whereIn('id', collect($data['items'])->pluck('id'))->get()->keyBy('id');

        $etiquetas = collect($data['items'])->flatMap(function ($item) use ($productos) {
            $p = $productos[$item['id']];

            return array_fill(0, (int) $item['copias'], $p);
        });

        $simbolo = Setting::obtener('sistema_simbolo_moneda', '$');
        $negocio = Setting::obtener('negocio_nombre', config('app.name'));

        return view('etiquetas.hoja', compact('etiquetas', 'columnas', 'simbolo', 'negocio'));
    }
}
