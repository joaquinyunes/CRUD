<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductoRequest;
use App\Http\Resources\ProductoResource;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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

    public function store(ProductoRequest $request): ProductoResource
    {
        $producto = Producto::create($request->datosParaGuardar());

        return new ProductoResource($producto->load('categoria'));
    }

    public function show(Producto $producto): ProductoResource
    {
        return new ProductoResource($producto->load('categoria'));
    }

    public function update(ProductoRequest $request, Producto $producto): ProductoResource
    {
        $producto->update($request->validated());

        return new ProductoResource($producto->load('categoria'));
    }

    public function destroy(Producto $producto): JsonResponse
    {
        $producto->update(['estado' => 'eliminado']);

        return response()->json(['message' => 'Producto eliminado correctamente.']);
    }
}
