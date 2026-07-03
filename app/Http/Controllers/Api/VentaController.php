<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VentaResource;
use App\Models\Venta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class VentaController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Venta::with(['cliente', 'user', 'detalles.producto']);

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where('numero', 'like', "%{$buscar}%")
                  ->orWhereHas('cliente', fn ($q) => $q->where('nombre', 'like', "%{$buscar}%"));
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('fecha_desde')) {
            $query->where('fecha', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('fecha', '<=', $request->fecha_hasta);
        }

        $ventas = $query->orderBy('fecha', 'desc')->paginate(20);

        return VentaResource::collection($ventas);
    }

    public function store(Request $request): VentaResource
    {
        $validated = $request->validate([
            'cliente_id'             => ['required', 'exists:clientes,id'],
            'fecha'                  => ['required', 'date'],
            'estado'                 => ['required', 'in:pendiente,completada,cancelada'],
            'detalles'               => ['required', 'array', 'min:1'],
            'detalles.*.producto_id' => ['required', 'exists:productos,id'],
            'detalles.*.cantidad'    => ['required', 'integer', 'min:1'],
            'detalles.*.precio'      => ['required', 'numeric', 'min:0'],
        ]);

        $venta = DB::transaction(function () use ($validated) {
            $numero = Venta::max('id') + 1;
            $numero = 'VTA-' . str_pad($numero, 5, '0', STR_PAD_LEFT);

            $detalles = collect($validated['detalles'])->map(function ($item) {
                $subtotal = $item['cantidad'] * $item['precio'];
                return array_merge($item, ['subtotal' => $subtotal]);
            });

            $total = $detalles->sum('subtotal');

            $venta = Venta::create([
                'numero'     => $numero,
                'cliente_id' => $validated['cliente_id'],
                'fecha'      => $validated['fecha'],
                'total'      => $total,
                'estado'     => $validated['estado'],
                'user_id'    => auth()->id(),
            ]);

            foreach ($detalles as $detalle) {
                $venta->detalles()->create($detalle);
            }

            return $venta;
        });

        return new VentaResource($venta->load(['detalles.producto', 'cliente', 'user']));
    }

    public function show(Venta $venta): VentaResource
    {
        $venta->load(['detalles.producto', 'cliente', 'user']);

        return new VentaResource($venta);
    }

    public function destroy(Venta $venta): JsonResponse
    {
        $venta->delete();

        return response()->json(['message' => 'Venta eliminada correctamente.']);
    }
}
