<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\StockInsuficienteException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CompraRequest;
use App\Models\Compra;
use App\Services\CompraService;
use App\Services\StockDocumentoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompraController extends Controller
{
    public function __construct(
        private StockDocumentoService $stockDoc,
        private CompraService $compras,
    ) {}

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
            abort(422, 'No se puede anular: '.$e->getMessage());
        }

        return response()->json(['message' => 'Compra anulada correctamente.']);
    }
}
