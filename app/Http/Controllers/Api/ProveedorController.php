<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

    public function store(Request $request): ProveedorResource
    {
        $validated = $request->validate([
            'nombre'    => ['required', 'string', 'max:255'],
            'cuit'      => ['nullable', 'string', 'max:20'],
            'telefono'  => ['nullable', 'string', 'max:50'],
            'email'     => ['nullable', 'email', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
        ]);

        $proveedor = Proveedor::create($validated);

        return new ProveedorResource($proveedor);
    }

    public function show(Proveedor $proveedor): ProveedorResource
    {
        return new ProveedorResource($proveedor);
    }

    public function update(Request $request, Proveedor $proveedor): ProveedorResource
    {
        $validated = $request->validate([
            'nombre'    => ['required', 'string', 'max:255'],
            'cuit'      => ['nullable', 'string', 'max:20'],
            'telefono'  => ['nullable', 'string', 'max:50'],
            'email'     => ['nullable', 'email', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
        ]);

        $proveedor->update($validated);

        return new ProveedorResource($proveedor);
    }

    public function destroy(Proveedor $proveedor): JsonResponse
    {
        $proveedor->delete();

        return response()->json(['message' => 'Proveedor eliminado correctamente.']);
    }
}
