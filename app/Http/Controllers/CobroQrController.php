<?php

namespace App\Http\Controllers;

use App\Models\PagoPasarela;
use App\Services\PasarelaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CobroQrController extends Controller
{
    public function __construct(private PasarelaService $pasarela) {}

    public function crear(Request $request): JsonResponse
    {
        $data = $request->validate([
            'monto' => ['required', 'numeric', 'gt:0'],
            'referencia' => ['required', 'string', 'max:60'],
        ]);

        try {
            $pago = $this->pasarela->crearCobro((float) $data['monto'], $data['referencia']);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($this->payload($pago));
    }

    public function estado(PagoPasarela $pago): JsonResponse
    {
        return response()->json($this->payload($this->pasarela->estado($pago)));
    }

    public function cancelar(PagoPasarela $pago): JsonResponse
    {
        $this->pasarela->cancelar($pago);

        return response()->json($this->payload($pago->fresh()));
    }

    private function payload(PagoPasarela $pago): array
    {
        return [
            'id' => $pago->id,
            'estado' => $pago->estado,
            'qr_data' => $pago->qr_data,
            'monto' => (float) $pago->monto,
        ];
    }
}
