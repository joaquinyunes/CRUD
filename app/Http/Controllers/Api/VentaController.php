<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ReglaDeNegocioException;
use App\Exceptions\StockInsuficienteException;
use App\Http\Controllers\Controller;
use App\Http\Requests\VentaRequest;
use App\Http\Resources\VentaResource;
use App\Models\Venta;
use App\Services\StockDocumentoService;
use App\Services\VentaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VentaController extends Controller
{
    public function __construct(
        private StockDocumentoService $stockDoc,
        private VentaService $ventas,
    ) {}

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

    /**
     * Misma validación, misma numeración y mismas reglas de negocio que la
     * pantalla de carga: las dos entradas llaman al mismo servicio.
     */
    public function store(VentaRequest $request): VentaResource
    {
        try {
            $venta = $this->ventas->crear($request->validated());
        } catch (ReglaDeNegocioException $e) {
            throw ValidationException::withMessages([$e->campo => $e->getMessage()]);
        } catch (StockInsuficienteException $e) {
            throw ValidationException::withMessages(['detalles' => $e->getMessage()]);
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
