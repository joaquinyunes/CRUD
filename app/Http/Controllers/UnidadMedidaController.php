<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\UnidadMedida;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UnidadMedidaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = UnidadMedida::query();

        if ($request->filled('buscar')) {
            $query->where(function ($q) use ($request) {
                $q->where('nombre', 'like', '%'.$request->input('buscar').'%')
                    ->orWhere('abreviacion', 'like', '%'.$request->input('buscar').'%');
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
            'nombre'      => ['required', 'string', 'max:100'],
            'abreviacion' => ['required', 'string', 'max:10'],
            'estado'      => ['boolean'],
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
            'nombre'      => ['required', 'string', 'max:100'],
            'abreviacion' => ['required', 'string', 'max:10'],
            'estado'      => ['boolean'],
        ]);

        $validated['estado'] = $request->boolean('estado');

        $unidad_medida->update($validated);

        return response()->json([
            'message' => 'Unidad de medida actualizada correctamente.',
            'data'    => $unidad_medida,
        ]);
    }

    /**
     * Borrarla dejaba los productos sin unidad en silencio (la clave foránea es
     * nullOnDelete). Si está en uso, se desactiva.
     */
    public function destroy(UnidadMedida $unidad_medida): JsonResponse
    {
        $productos = Producto::where('unidad_medida_id', $unidad_medida->id)->count();

        if ($productos > 0) {
            $unidad_medida->update(['estado' => false]);

            return response()->json([
                'message' => "Unidad desactivada: la usan {$productos} ".
                    ($productos === 1 ? 'producto' : 'productos').'. Ya no aparece al cargar productos nuevos.',
            ]);
        }

        $unidad_medida->delete();

        return response()->json([
            'message' => 'Unidad de medida eliminada correctamente.',
        ]);
    }
}
