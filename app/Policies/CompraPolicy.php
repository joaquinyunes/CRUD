<?php

namespace App\Policies;

use App\Models\Compra;
use App\Models\User;

class CompraPolicy
{
    /**
     * Quien tiene `compras.gestionar_todas` (o el comodín) actúa sobre cualquier
     * comprobante; el resto, solo sobre los propios. No se compara el nombre
     * del rol: un rol nuevo con ese permiso manda igual que Supervisor.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->role?->tienePermiso('compras.gestionar_todas') ? true : null;
    }

    public function view(User $user, Compra $compra): bool
    {
        return $user->id === $compra->user_id;
    }

    public function update(User $user, Compra $compra): bool
    {
        return $user->id === $compra->user_id;
    }

    public function delete(User $user, Compra $compra): bool
    {
        return $user->id === $compra->user_id;
    }
}
