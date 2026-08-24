<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\StockInsuficienteException;
use App\Http\Controllers\Controller;
use App\Http\Resources\VentaResource;
use App\Models\Producto;
use App\Models\Venta;
use App\Services\StockDocumentoService;
use App\Support\CalculadorTotales;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class VentaController extends Controller
{
    public function __construct(private StockDocumentoService $stockDoc) {}

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

        try {
            $venta = DB::transaction(function () use ($validated) {
                $numero = 'VTA-' . str_pad((string) (Venta::max('id') + 1), 5, '0', STR_PAD_LEFT);

                $costos = Producto::whereIn('id', collect($validated['detalles'])->pluck('producto_id'))
                    ->pluck('precio_compra', 'id');

                $detalles = collect($validated['detalles'])->map(function ($item) use ($costos) {
                    return [
                        'producto_id'    => $item['producto_id'],
                        'cantidad'       => (int) $item['cantidad'],
                        'precio'         => round((float) $item['precio'], 2),
                        'costo_unitario' => round((float) ($costos[$item['producto_id']] ?? 0), 2),
                        'subtotal'       => round($item['cantidad'] * $item['precio'], 2),
                    ];
                });

                $totales = CalculadorTotales::calcular($detalles->all(), null, 0);

                $venta = Venta::create([
                    'numero'      => $numero,
                    'cliente_id'  => $validated['cliente_id'],
                    'fecha'       => $validated['fecha'],
                    'subtotal'    => $totales['subtotal'],
                    'impuesto'    => $totales['impuesto'],
                    'total_final' => $totales['total'],
                    'total'       => $totales['total'],
                    'estado'      => $validated['estado'],
                    'user_id'     => auth()->id(),
                ]);

                $venta->detalles()->createMany($detalles->all());

                if ($venta->estado === 'completada') {
                    $this->stockDoc->aplicarVenta($venta);
                }

                return $venta;
            });
        } catch (StockInsuficienteException $e) {
            abort(422, $e->getMessage());
        }

        return new VentaResource($venta->load(['detalles.producto', 'cliente', 'user']));
    }

    public function show(Venta $venta): VentaResource
    {
        $venta->load(['detalles.producto', 'cliente', 'user']);

        return new VentaResource($venta);
    }

    public function destroy(Venta $venta): JsonResponse
    {
        DB::transaction(function () use ($venta) {
            $this->stockDoc->revertirVenta($venta);
            $venta->update(['estado' => 'anulada', 'motivo_anulacion' => 'Anulada vía API']);
        });

        return response()->json(['message' => 'Venta anulada correctamente.']);
    }
}
