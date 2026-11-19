<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ReglaDeNegocioException;
use App\Exceptions\StockInsuficienteException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CompraRequest;
use App\Models\Compra;
use App\Services\CompraService;
use App\Services\StockDocumentoService;
use App\Support\Mongo\Transaccion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CompraController extends Controller
{
    public function __construct(
        private StockDocumentoService $stockDoc,
        private CompraService $compras,
    ) {}

    public function index(Request $request)
    {
        $query = Compra::with(['proveedor', 'user', 'detalles.producto'])->visiblesPara($request->user());

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            // Agrupado: un orWhere suelto se saltaría el filtro de visibilidad.
            $query->where(fn ($q) => $q->where('numero', 'like', "%{$buscar}%")
                ->orWhereHas('proveedor', fn ($p) => $p->where('nombre', 'like', "%{$buscar}%")));
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $compras = $query->orderBy('fecha', 'desc')->paginate(20);

        return response()->json($compras);
    }

    /**
     * Misma validación, misma numeración y mismas reglas que la pantalla de
     * carga: las dos entradas llaman al mismo servicio.
     */
    public function store(CompraRequest $request): JsonResponse
    {
        try {
            $compra = $this->compras->crear($request->validated());
        } catch (StockInsuficienteException $e) {
            throw ValidationException::withMessages(['detalles' => $e->getMessage()]);
        }

        return response()->json([
            'message' => 'Compra registrada correctamente.',
            'data'    => $compra->load(['detalles.producto', 'proveedor', 'user']),
        ], 201);
    }

    public function show(Compra $compra): JsonResponse
    {
        $this->authorize('view', $compra);
        $compra->load(['detalles.producto', 'proveedor', 'user']);

        return response()->json($compra);
    }

    public function destroy(Compra $compra): JsonResponse
    {
        $this->authorize('delete', $compra);

        try {
            Transaccion::en(fn () => $this->compras->anular($compra, 'Anulada vía API'));
        } catch (StockInsuficienteException|ReglaDeNegocioException $e) {
            abort(422, 'No se puede anular: '.$e->getMessage());
        }

        return response()->json(['message' => 'Compra anulada correctamente.']);
    }
}
