<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    public function update(Request $request, Role $role): RedirectResponse
    {
        if ($role->nombre === Role::ADMINISTRADOR) {
            return redirect()
                ->route('roles.index')
                ->with('error', 'Los permisos del rol Administrador no se pueden modificar.');
        }

        $request->validate([
            'permisos'   => ['nullable', 'array'],
            'permisos.*' => ['string', 'exists:permissions,clave'],
        ]);

        $permisosSeleccionados = $request->input('permisos', []);

        $permisosIds = Permission::whereIn('clave', $permisosSeleccionados)
            ->pluck('id')
            ->toArray();

        $role->permissions()->sync($permisosIds);

        return redirect()
            ->route('roles.index')
            ->with('success', "Permisos del rol \"{$role->nombre}\" actualizados correctamente.");
    }

    public function usuarios(): View
    {
        $usuarios = User::with('role')->orderBy('name')->get();
        $roles    = Role::all();

        return view('roles.usuarios', compact('usuarios', 'roles'));
    }

    public function asignarRol(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'role_id' => ['nullable', 'exists:roles,id'],
        ]);

        if ($user->id === Auth::id() && $request->role_id === null) {
            return redirect()
                ->route('roles.usuarios')
                ->with('error', 'No podés quitarte el rol a vos mismo.');
        }

        $user->role_id = $request->input('role_id');
        $user->save();

        $rolNombre = $request->role_id
            ? Role::find($request->role_id)?->nombre ?? 'desconocido'
            : 'sin rol';

        return redirect()
            ->route('roles.usuarios')
            ->with('success', "Rol de \"{$user->name}\" actualizado a \"{$rolNombre}\".");
    }
}
