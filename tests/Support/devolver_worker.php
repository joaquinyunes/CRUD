<?php

/**
 * Proceso hijo del test de concurrencia de devoluciones: espera la hora de largada y
 * devuelve <cantidad> unidades de una venta, <veces> veces seguidas.
 *
 * Uso: php devolver_worker.php <venta_id> <producto_id> <cantidad> <largada_unix_float> <veces>
 * Imprime una línea JSON: lista de {"ok":true} o {"ok":false,"error":"..."}.
 */

use App\Exceptions\ReglaDeNegocioException;
use App\Models\Venta;
use App\Services\DevolucionService;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../../vendor/autoload.php';

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

[, $ventaId, $productoId, $cantidad, $largada, $veces] = $argv;

while (microtime(true) < (float) $largada) {
    usleep(200);
}

$resultados = [];

for ($i = 0; $i < (int) $veces; $i++) {
    try {
        $app->make(DevolucionService::class)->devolverVenta(
            Venta::findOrFail((int) $ventaId),
            [['producto_id' => (int) $productoId, 'cantidad' => (int) $cantidad]],
            'concurrencia',
            false,
            null,
        );
        $resultados[] = ['ok' => true];
    } catch (ReglaDeNegocioException $e) {
        $resultados[] = ['ok' => false, 'error' => 'regla'];
    } catch (Throwable $e) {
        $resultados[] = ['ok' => false, 'error' => $e::class.': '.$e->getMessage()];
    }
}

echo json_encode($resultados);
