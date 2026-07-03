<?php

namespace App\Listeners;

use App\Events\VentaCreada;
use App\Models\Notificacion;
use App\Models\User;

class NotificarVentaCreada
{
    public function handle(VentaCreada $event): void
    {
        $venta = $event->venta;
        $cliente = $venta->cliente->nombre . ' ' . $venta->cliente->apellido;

        $admins = User::whereHas('role', function ($q) {
            $q->where('nombre', 'Administrador');
        })->get();

        foreach ($admins as $admin) {
            Notificacion::create([
                'titulo'  => 'Nueva venta registrada',
                'mensaje' => "Venta #{$venta->numero} a {$cliente} por $" . number_format($venta->total, 2, ',', '.'),
                'tipo'    => 'venta',
                'url'     => '/ventas/' . $venta->id,
                'user_id' => $admin->id,
            ]);
        }
    }
}
