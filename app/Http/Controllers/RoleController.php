<?php

namespace App\Http\Controllers;

use App\Http\Requests\RolRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::all();
        $usuariosPorRol = User::all(['role_id'])->countBy('role_id');

        foreach ($roles as $rol) {
            $rol->setAttribute('permissions_count', count($rol->permission_ids ?? []));
            $rol->setAttribute('users_count', $usuariosPorRol[$rol->id] ?? 0);
        }

        return view('roles.index', compact('roles'));
    }

    public function edit(Role $role): View
    {
        $role->load('permissions');

        $todosLosPermisos = Permission::all()->groupBy(function ($permission) {
            return explode('.', $permission->clave)[0];
        });

        $permisosAsignados = $role->permissions->pluck('clave')->toArray();

        return view('roles.edit', compact('role', 'todosLosPermisos', 'permisosAsignados'));
    }

    public function update(RolRequest $request, Role $role): RedirectResponse
    {
        // Un rol con el comodín (el que puede todo) no se toca desde acá, se llame como se llame.
        if (in_array(Role::TODO, Role::clavesDe($role->id), true)) {
            return redirect()
                ->route('roles.index')
                ->with('error', 'Los permisos de un rol que puede todo no se pueden modificar.');
        }

        $permisosSeleccionados = $request->input('permisos', []);
        $actor = $request->user();

        // Mínimo privilegio: no se agregan permisos que uno no tiene (el comodín, solo quien lo tiene)
        // ni se reescribe un rol que tiene permisos que uno no tiene (se los quitaría a otros).
        if (! $actor->puedeOtorgar($permisosSeleccionados) || ! $actor->puedeOtorgar(Role::clavesDe($role->id))) {
            return redirect()
                ->route('roles.index')
                ->with('error', 'No podés dar ni quitar permisos que vos no tenés.');
        }

        $permisosIds = Permission::whereIn('clave', $permisosSeleccionados)
            ->pluck('id')
            ->toArray();

        $role->sincronizarPermisos($permisosIds);

        return redirect()
            ->route('roles.index')
            ->with('success', "Permisos del rol \"{$role->nombre}\" actualizados correctamente.");
    }
}
