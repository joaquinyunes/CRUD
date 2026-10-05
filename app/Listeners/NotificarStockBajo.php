<?php

namespace App\Listeners;

use App\Events\StockBajo;
use App\Models\Notificacion;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotificarStockBajo implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(StockBajo $event): void
    {
        $producto = $event->producto;

        $admins = User::administradores()->get();

        foreach ($admins as $admin) {
            Notificacion::create([
                'titulo'  => 'Stock bajo',
                'mensaje' => "El producto \"{$producto->nombre}\" tiene stock bajo ({$producto->stock} unidades, mínimo: {$producto->stock_minimo}).",
                'tipo'    => 'stock',
                'url'     => '/productos/'.$producto->id.'/editar',
                'user_id' => $admin->id,
            ]);
        }
    }
}
