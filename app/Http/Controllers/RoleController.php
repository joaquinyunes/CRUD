<?php

namespace App\Http\Controllers;

use App\Http\Requests\RolRequest;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::withCount(['permissions', 'users'])->get();

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
        if ($role->nombre === Role::ADMINISTRADOR) {
            return redirect()
                ->route('roles.index')
                ->with('error', 'Los permisos del rol Administrador no se pueden modificar.');
        }

        $permisosSeleccionados = $request->input('permisos', []);

        $permisosIds = Permission::whereIn('clave', $permisosSeleccionados)
            ->pluck('id')
            ->toArray();

        $role->permissions()->sync($permisosIds);

        return redirect()
            ->route('roles.index')
            ->with('success', "Permisos del rol \"{$role->nombre}\" actualizados correctamente.");
    }
}
