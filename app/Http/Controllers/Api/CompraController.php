<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Compra;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompraController extends Controller
{
    public function index(Request $request)
    {
        $query = Compra::with(['proveedor', 'user', 'detalles.producto']);

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where('numero', 'like', "%{$buscar}%")
                  ->orWhereHas('proveedor', fn ($q) => $q->where('nombre', 'like', "%{$buscar}%"));
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $compras = $query->orderBy('fecha', 'desc')->paginate(20);

        return response()->json($compras);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'proveedor_id'            => ['required', 'exists:proveedores,id'],
            'fecha'                   => ['required', 'date'],
            'estado'                  => ['required', 'in:pendiente,completada,cancelada'],
            'detalles'                => ['required', 'array', 'min:1'],
            'detalles.*.producto_id'  => ['required', 'exists:productos,id'],
            'detalles.*.cantidad'     => ['required', 'integer', 'min:1'],
            'detalles.*.precio'       => ['required', 'numeric', 'min:0'],
        ]);

        $compra = DB::transaction(function () use ($validated) {
            $numero = Compra::max('id') + 1;
            $numero = 'COM-' . str_pad($numero, 5, '0', STR_PAD_LEFT);

            $detalles = collect($validated['detalles'])->map(function ($item) {
                $subtotal = $item['cantidad'] * $item['precio'];
                return array_merge($item, ['subtotal' => $subtotal]);
            });

            $total = $detalles->sum('subtotal');

            $compra = Compra::create([
                'numero'       => $numero,
                'proveedor_id' => $validated['proveedor_id'],
                'fecha'        => $validated['fecha'],
                'total'        => $total,
                'estado'       => $validated['estado'],
                'user_id'      => auth()->id(),
            ]);

            foreach ($detalles as $detalle) {
                $compra->detalles()->create($detalle);
            }

            return $compra;
        });

        return response()->json([
            'message' => 'Compra registrada correctamente.',
            'data'    => $compra->load(['detalles.producto', 'proveedor', 'user']),
        ]);
    }

    public function show(Compra $compra): JsonResponse
    {
        $compra->load(['detalles.producto', 'proveedor', 'user']);

        return response()->json($compra);
    }

    public function destroy(Compra $compra): JsonResponse
    {
        $compra->delete();

        return response()->json(['message' => 'Compra eliminada correctamente.']);
    }
}
