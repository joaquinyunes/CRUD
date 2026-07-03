<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoriaResource;
use App\Models\Categoria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoriaController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $categorias = Categoria::where('estado', true)->orderBy('nombre')->paginate(50);

        return CategoriaResource::collection($categorias);
    }

    public function store(Request $request): CategoriaResource
    {
        $validated = $request->validate([
            'nombre'      => ['required', 'string', 'max:255', 'unique:categorias,nombre'],
            'descripcion' => ['nullable', 'string'],
            'estado'      => ['required', 'boolean'],
        ]);

        $categoria = Categoria::create($validated);

        return new CategoriaResource($categoria);
    }

    public function show(Categoria $categoria): CategoriaResource
    {
        return new CategoriaResource($categoria);
    }

    public function update(Request $request, Categoria $categoria): CategoriaResource
    {
        $validated = $request->validate([
            'nombre'      => ['required', 'string', 'max:255', 'unique:categorias,nombre,' . $categoria->id],
            'descripcion' => ['nullable', 'string'],
            'estado'      => ['required', 'boolean'],
        ]);

        $categoria->update($validated);

        return new CategoriaResource($categoria);
    }

    public function destroy(Categoria $categoria): JsonResponse
    {
        $categoria->update(['estado' => false]);

        return response()->json(['message' => 'Categoría desactivada correctamente.']);
    }
}
