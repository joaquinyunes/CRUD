<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\StockInsuficienteException;
use App\Http\Controllers\Controller;
use App\Models\Compra;
use App\Models\Producto;
use App\Services\StockDocumentoService;
use App\Support\CalculadorTotales;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompraController extends Controller
{
    public function __construct(private StockDocumentoService $stockDoc) {}

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
            $numero = 'COM-' . str_pad((string) (Compra::max('id') + 1), 5, '0', STR_PAD_LEFT);

            $detalles = collect($validated['detalles'])->map(function ($item) {
                return [
                    'producto_id' => $item['producto_id'],
                    'cantidad'    => (int) $item['cantidad'],
                    'precio'      => round((float) $item['precio'], 2),
                    'subtotal'    => round($item['cantidad'] * $item['precio'], 2),
                ];
            });

            $totales = CalculadorTotales::calcular($detalles->all(), null, 0);

            $compra = Compra::create([
                'numero'       => $numero,
                'proveedor_id' => $validated['proveedor_id'],
                'fecha'        => $validated['fecha'],
                'subtotal'     => $totales['subtotal'],
                'impuesto'     => $totales['impuesto'],
                'total_final'  => $totales['total'],
                'total'        => $totales['total'],
                'estado'       => $validated['estado'],
                'user_id'      => auth()->id(),
            ]);

            $compra->detalles()->createMany($detalles->all());

            if ($compra->estado === 'completada') {
                $this->stockDoc->aplicarCompra($compra);
                foreach ($detalles as $d) {
                    Producto::whereKey($d['producto_id'])->update(['precio_compra' => $d['precio']]);
                }
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
        try {
            DB::transaction(function () use ($compra) {
                $this->stockDoc->revertirCompra($compra);
                $compra->update(['estado' => 'anulada', 'motivo_anulacion' => 'Anulada vía API']);
            });
        } catch (StockInsuficienteException $e) {
            abort(422, 'No se puede anular: ' . $e->getMessage());
        }

        return response()->json(['message' => 'Compra anulada correctamente.']);
    }
}
