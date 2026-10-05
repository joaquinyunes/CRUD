<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoriaRequest;
use App\Http\Resources\CategoriaResource;
use App\Models\Categoria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoriaController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $categorias = Categoria::where('estado', true)->orderBy('nombre')->paginate(50);

        return CategoriaResource::collection($categorias);
    }

    public function store(CategoriaRequest $request): CategoriaResource
    {
        $validated = $request->datosParaGuardar();

        $categoria = Categoria::create($validated);

        return new CategoriaResource($categoria);
    }

    public function show(Categoria $categoria): CategoriaResource
    {
        return new CategoriaResource($categoria);
    }

    public function update(CategoriaRequest $request, Categoria $categoria): CategoriaResource
    {
        $validated = $request->datosParaGuardar();

        $categoria->update($validated);

        return new CategoriaResource($categoria);
    }

    public function destroy(Categoria $categoria): JsonResponse
    {
        $categoria->update(['estado' => false]);

        return response()->json(['message' => 'Categoría desactivada correctamente.']);
    }
}
