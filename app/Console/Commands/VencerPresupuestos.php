<?php

namespace App\Console\Commands;

use App\Models\Presupuesto;
use Illuminate\Console\Command;

/**
 * Pasa a "vencido" los presupuestos cuya validez ya expiró.
 *
 * El listado ya ofrecía filtrar por ese estado, pero nadie lo escribía nunca: el
 * vencimiento se calculaba al vuelo para mostrarlo y el estado guardado seguía
 * diciendo "enviado" para siempre.
 */
class VencerPresupuestos extends Command
{
    protected $signature = 'presupuestos:vencer';

    protected $description = 'Marca como vencidos los presupuestos cuya validez expiró.';

    public function handle(): int
    {
        $vencidos = 0;

        Presupuesto::whereIn('estado', ['borrador', 'enviado'])
            ->whereNull('venta_id')
            ->chunkById(200, function ($presupuestos) use (&$vencidos) {
                foreach ($presupuestos as $presupuesto) {
                    if ($presupuesto->estaVencido()) {
                        $presupuesto->update(['estado' => 'vencido']);
                        $vencidos++;
                    }
                }
            });

        $this->info("Presupuestos vencidos: {$vencidos}.");

        return self::SUCCESS;
    }
}
