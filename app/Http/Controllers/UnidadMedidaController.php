<?php

namespace App\Http\Controllers;

use App\Models\UnidadMedida;
use App\Traits\Auditable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UnidadMedidaController extends Controller
{
    use Auditable;

    public function index(Request $request): JsonResponse
    {
        $query = UnidadMedida::query();

        if ($request->filled('buscar')) {
            $query->where(function ($q) use ($request) {
                $q->where('nombre', 'like', '%' . $request->input('buscar') . '%')
                    ->orWhere('abreviacion', 'like', '%' . $request->input('buscar') . '%');
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->input('estado') === 'activo');
        }

        $unidades = $query->orderBy('nombre')->get();

        return response()->json($unidades);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre'     => ['required', 'string', 'max:100'],
            'abreviacion' => ['required', 'string', 'max:10'],
            'estado'     => ['boolean'],
        ]);

        $validated['estado'] = $request->boolean('estado');

        $unidad = UnidadMedida::create($validated);

        return response()->json([
            'message' => 'Unidad de medida creada correctamente.',
            'data'    => $unidad,
        ], Response::HTTP_CREATED);
    }

    public function update(Request $request, UnidadMedida $unidad_medida): JsonResponse
    {
        $validated = $request->validate([
            'nombre'     => ['required', 'string', 'max:100'],
            'abreviacion' => ['required', 'string', 'max:10'],
            'estado'     => ['boolean'],
        ]);

        $validated['estado'] = $request->boolean('estado');

        $unidad_medida->update($validated);

        return response()->json([
            'message' => 'Unidad de medida actualizada correctamente.',
            'data'    => $unidad_medida,
        ]);
    }

    public function destroy(UnidadMedida $unidad_medida): JsonResponse
    {
        $unidad_medida->delete();

        return response()->json([
            'message' => 'Unidad de medida eliminada correctamente.',
        ]);
    }
}
