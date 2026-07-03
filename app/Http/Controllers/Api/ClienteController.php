<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClienteResource;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClienteController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Cliente::where('estado', 'activo');

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('apellido', 'like', "%{$buscar}%")
                  ->orWhere('documento', 'like', "%{$buscar}%");
            });
        }

        $clientes = $query->orderBy('nombre')->paginate(20);

        return ClienteResource::collection($clientes);
    }

    public function store(Request $request): ClienteResource
    {
        $validated = $request->validate([
            'nombre'        => ['required', 'string', 'max:255'],
            'apellido'      => ['required', 'string', 'max:255'],
            'documento'     => ['nullable', 'string', 'max:20'],
            'email'         => ['nullable', 'email', 'max:255'],
            'telefono'      => ['nullable', 'string', 'max:50'],
            'direccion'     => ['nullable', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string'],
            'estado'        => ['required', 'in:activo,archivado'],
        ]);

        $cliente = Cliente::create($validated);

        return new ClienteResource($cliente);
    }

    public function show(Cliente $cliente): ClienteResource
    {
        return new ClienteResource($cliente);
    }

    public function update(Request $request, Cliente $cliente): ClienteResource
    {
        $validated = $request->validate([
            'nombre'        => ['required', 'string', 'max:255'],
            'apellido'      => ['required', 'string', 'max:255'],
            'documento'     => ['nullable', 'string', 'max:20'],
            'email'         => ['nullable', 'email', 'max:255'],
            'telefono'      => ['nullable', 'string', 'max:50'],
            'direccion'     => ['nullable', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string'],
            'estado'        => ['required', 'in:activo,archivado'],
        ]);

        $cliente->update($validated);

        return new ClienteResource($cliente);
    }

    public function destroy(Cliente $cliente): JsonResponse
    {
        $cliente->update(['estado' => 'eliminado']);

        return response()->json(['message' => 'Cliente eliminado correctamente.']);
    }
}
