<?php

namespace App\Policies;

use App\Models\Compra;
use App\Models\Role;
use App\Models\User;

class CompraPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        $rol = $user->role?->nombre;

        if (in_array($rol, [Role::ADMINISTRADOR, 'admin', Role::SUPERVISOR], true)) {
            return true;
        }

        return null;
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
