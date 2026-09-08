<?php

namespace App\Http\Controllers;

use App\Services\PasarelaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function mercadoPago(Request $request, PasarelaService $pasarela): JsonResponse
    {
        $secret = config('mercadopago.webhook_secret');
        if ($secret && ! hash_equals($secret, (string) $request->query('secret'))) {
            return response()->json(['error' => 'firma inválida'], 401);
        }

        $pasarela->webhook($request->all());

        // MP espera 200/201 rápido; el procesamiento pesado ya quedó hecho.
        return response()->json(['ok' => true]);
    }
}
