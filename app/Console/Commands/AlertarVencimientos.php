<?php

namespace App\Console\Commands;

use App\Models\Notificacion;
use App\Models\User;
use App\Services\LoteService;
use Illuminate\Console\Command;

class AlertarVencimientos extends Command
{
    protected $signature = 'lotes:alertar-vencimientos';

    protected $description = 'Notifica a los administradores sobre lotes próximos a vencer o vencidos.';

    public function handle(LoteService $lotes): int
    {
        $porVencer = $lotes->porVencer();

        if ($porVencer->isEmpty()) {
            $this->info('Sin lotes por vencer.');

            return self::SUCCESS;
        }

        $vencidos = $porVencer->filter(fn ($l) => $l->vencimiento->isPast())->count();
        $mensaje = "{$porVencer->count()} lote(s) por vencer".($vencidos ? " ({$vencidos} ya vencido/s)" : '').'.';

        $admins = User::whereHas('role', fn ($q) => $q->whereIn('nombre', ['Administrador', 'admin', 'Supervisor']))->get();

        foreach ($admins as $admin) {
            Notificacion::create([
                'titulo' => 'Vencimientos de stock',
                'mensaje' => $mensaje,
                'tipo' => 'stock',
                'url' => '/lotes',
                'user_id' => $admin->id,
            ]);
        }

        $this->info($mensaje);

        return self::SUCCESS;
    }
}
