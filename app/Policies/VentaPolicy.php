<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Venta;

class VentaPolicy
{
    /**
     * Quien tiene `ventas.gestionar_todas` (o el comodín) actúa sobre cualquier
     * comprobante; el resto, solo sobre los propios. No se compara el nombre
     * del rol: un rol nuevo con ese permiso manda igual que Supervisor.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->role?->tienePermiso('ventas.gestionar_todas') ? true : null;
    }

    public function view(User $user, Venta $venta): bool
    {
        return $user->id === $venta->user_id;
    }

    public function update(User $user, Venta $venta): bool
    {
        return $user->id === $venta->user_id;
    }

    public function delete(User $user, Venta $venta): bool
    {
        return $user->id === $venta->user_id;
    }
}
