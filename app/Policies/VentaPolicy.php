<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use App\Models\Venta;

class VentaPolicy
{
    /**
     * Administrador y Supervisor tienen acceso total; el resto solo a lo propio.
     */
    public function before(User $user, string $ability): ?bool
    {
        $rol = $user->role?->nombre;

        if (in_array($rol, [Role::ADMINISTRADOR, 'admin', Role::SUPERVISOR], true)) {
            return true;
        }

        return null;
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
