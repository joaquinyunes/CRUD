<?php

/**
 * Proceso hijo del test de concurrencia: espera la hora de largada y emite una venta.
 *
 * Uso: php vender_worker.php <producto_id> <cliente_id> <cantidad> <largada_unix_float> <veces>
 * Repite la venta <veces> veces seguidas (para que los procesos se pisen de verdad) e
 * imprime una línea JSON con una lista de {"ok":true,"numero":"VTA-00001"} o {"ok":false,"error":"..."}.
 */

use App\Exceptions\StockInsuficienteException;
use App\Services\VentaService;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../../vendor/autoload.php';

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

[, $productoId, $clienteId, $cantidad, $largada, $veces] = $argv;

while (microtime(true) < (float) $largada) {
    usleep(200);
}

$resultados = [];

for ($i = 0; $i < (int) $veces; $i++) {
    try {
        $venta = $app->make(VentaService::class)->crear([
            'cliente_id' => (int) $clienteId,
            'fecha'      => date('Y-m-d'),
            'estado'     => 'completada',
            'detalles'   => [['producto_id' => (int) $productoId, 'cantidad' => (int) $cantidad, 'precio' => 100]],
        ]);
        $resultados[] = ['ok' => true, 'numero' => $venta->numero];
    } catch (StockInsuficienteException) {
        $resultados[] = ['ok' => false, 'error' => 'stock'];
    } catch (Throwable $e) {
        $resultados[] = ['ok' => false, 'error' => $e::class.': '.$e->getMessage()];
    }
}

echo json_encode($resultados);
