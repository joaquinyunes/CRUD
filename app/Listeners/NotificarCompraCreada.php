<?php

namespace App\Listeners;

use App\Events\CompraCreada;
use App\Models\Notificacion;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotificarCompraCreada implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(CompraCreada $event): void
    {
        $compra = $event->compra;
        $proveedor = $compra->proveedor->nombre;

        $admins = User::administradores()->get();

        foreach ($admins as $admin) {
            Notificacion::create([
                'titulo'  => 'Nueva compra registrada',
                'mensaje' => "Compra #{$compra->numero} a {$proveedor} por $".number_format((float) $compra->total, 2, ',', '.'),
                'tipo'    => 'compra',
                'url'     => '/compras/'.$compra->id,
                'user_id' => $admin->id,
            ]);
        }
    }
}
