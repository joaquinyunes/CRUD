<?php

namespace App\Http\Controllers;

use App\Models\MetodoPago;
use App\Traits\Auditable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MetodoPagoController extends Controller
{
    use Auditable;

    public function index(Request $request): JsonResponse
    {
        $query = MetodoPago::query();

        if ($request->filled('buscar')) {
            $query->where(function ($q) use ($request) {
                $q->where('nombre', 'like', '%' . $request->input('buscar') . '%')
                    ->orWhere('codigo', 'like', '%' . $request->input('buscar') . '%');
            });
        }

        if ($request->filled('activo')) {
            $query->where('activo', $request->input('activo') === 'activo');
        }

        $metodos = $query->orderBy('orden')->orderBy('nombre')->get();

        return response()->json($metodos);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre'          => ['required', 'string', 'max:100'],
            'codigo'          => ['required', 'string', 'max:20', 'unique:metodos_pago,codigo'],
            'activo'          => ['boolean'],
            'permite_vuelto'  => ['boolean'],
            'orden'           => ['integer'],
        ]);

        $validated['activo']         = $request->boolean('activo');
        $validated['permite_vuelto'] = $request->boolean('permite_vuelto');
        $validated['orden']          = $request->input('orden', 0);

        $metodo = MetodoPago::create($validated);

        return response()->json([
            'message' => 'Método de pago creado correctamente.',
            'data'    => $metodo,
        ], Response::HTTP_CREATED);
    }

    public function update(Request $request, MetodoPago $metodo_pago): JsonResponse
    {
        $validated = $request->validate([
            'nombre'          => ['required', 'string', 'max:100'],
            'codigo'          => ['required', 'string', 'max:20', 'unique:metodos_pago,codigo,' . $metodo_pago->id],
            'activo'          => ['boolean'],
            'permite_vuelto'  => ['boolean'],
            'orden'           => ['integer'],
        ]);

        $validated['activo']         = $request->boolean('activo');
        $validated['permite_vuelto'] = $request->boolean('permite_vuelto');
        $validated['orden']          = $request->input('orden', 0);

        $metodo_pago->update($validated);

        return response()->json([
            'message' => 'Método de pago actualizado correctamente.',
            'data'    => $metodo_pago,
        ]);
    }

    public function destroy(MetodoPago $metodo_pago): JsonResponse
    {
        $metodo_pago->delete();

        return response()->json([
            'message' => 'Método de pago eliminado correctamente.',
        ]);
    }
}
