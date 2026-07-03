<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductoResource;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class ProductoController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Producto::with('categoria')->where('estado', 'activo');

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('codigo', 'like', "%{$buscar}%");
            });
        }

        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        $productos = $query->orderBy('nombre')->paginate(20);

        return ProductoResource::collection($productos);
    }

    public function store(Request $request): ProductoResource
    {
        $validated = $request->validate([
            'codigo'        => ['required', 'string', 'max:100', 'unique:productos,codigo'],
            'nombre'        => ['required', 'string', 'max:255'],
            'descripcion'   => ['nullable', 'string'],
            'categoria_id'  => ['required', 'exists:categorias,id'],
            'marca'         => ['nullable', 'string', 'max:100'],
            'precio_compra' => ['required', 'numeric', 'min:0'],
            'precio_venta'  => ['required', 'numeric', 'min:0'],
            'stock_minimo'  => ['required', 'integer', 'min:0'],
            'estado'        => ['required', 'in:activo,inactivo'],
        ]);

        $producto = Producto::create($validated);

        return new ProductoResource($producto->load('categoria'));
    }

    public function show(Producto $producto): ProductoResource
    {
        return new ProductoResource($producto->load('categoria'));
    }

    public function update(Request $request, Producto $producto): ProductoResource
    {
        $validated = $request->validate([
            'codigo'        => ['required', 'string', 'max:100', 'unique:productos,codigo,' . $producto->id],
            'nombre'        => ['required', 'string', 'max:255'],
            'descripcion'   => ['nullable', 'string'],
            'categoria_id'  => ['required', 'exists:categorias,id'],
            'marca'         => ['nullable', 'string', 'max:100'],
            'precio_compra' => ['required', 'numeric', 'min:0'],
            'precio_venta'  => ['required', 'numeric', 'min:0'],
            'stock_minimo'  => ['required', 'integer', 'min:0'],
            'estado'        => ['required', 'in:activo,inactivo'],
        ]);

        $producto->update($validated);

        return new ProductoResource($producto->load('categoria'));
    }

    public function destroy(Producto $producto): JsonResponse
    {
        $producto->update(['estado' => 'eliminado']);

        return response()->json(['message' => 'Producto eliminado correctamente.']);
    }
}
