<?php

namespace App\Listeners;

use App\Events\CompraCreada;
use App\Models\Notificacion;
use App\Models\User;

class NotificarCompraCreada
{
    public function handle(CompraCreada $event): void
    {
        $compra = $event->compra;
        $proveedor = $compra->proveedor->nombre;

        $admins = User::whereHas('role', function ($q) {
            $q->where('nombre', 'Administrador');
        })->get();

        foreach ($admins as $admin) {
            Notificacion::create([
                'titulo'  => 'Nueva compra registrada',
                'mensaje' => "Compra #{$compra->numero} a {$proveedor} por $" . number_format($compra->total, 2, ',', '.'),
                'tipo'    => 'compra',
                'url'     => '/compras/' . $compra->id,
                'user_id' => $admin->id,
            ]);
        }
    }
}
