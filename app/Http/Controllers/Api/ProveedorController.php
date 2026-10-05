<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProveedorRequest;
use App\Http\Resources\ProveedorResource;
use App\Models\Proveedor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProveedorController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Proveedor::query();

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where('nombre', 'like', "%{$buscar}%")
                ->orWhere('cuit', 'like', "%{$buscar}%");
        }

        $proveedores = $query->orderBy('nombre')->paginate(20);

        return ProveedorResource::collection($proveedores);
    }

    public function store(ProveedorRequest $request): ProveedorResource
    {
        $validated = $request->validated();

        $proveedor = Proveedor::create($validated);

        return new ProveedorResource($proveedor);
    }

    public function show(Proveedor $proveedor): ProveedorResource
    {
        return new ProveedorResource($proveedor);
    }

    public function update(ProveedorRequest $request, Proveedor $proveedor): ProveedorResource
    {
        $validated = $request->validated();

        $proveedor->update($validated);

        return new ProveedorResource($proveedor);
    }

    public function destroy(Proveedor $proveedor): JsonResponse
    {
        $proveedor->delete();

        return response()->json(['message' => 'Proveedor eliminado correctamente.']);
    }
}
