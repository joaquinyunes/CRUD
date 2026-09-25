<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Support\Orden;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::with('role');

        if ($request->filled('buscar')) {
            $buscar = $request->input('buscar');
            $query->where(function ($q) use ($buscar) {
                $q->where('name', 'like', "%{$buscar}%")
                    ->orWhere('email', 'like', "%{$buscar}%");
            });
        }

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->input('role_id'));
        }

        $usuarios = Orden::aplicar($query, [
            'nombre' => 'name',
            'email'  => 'email',
            'rol'    => Role::select('nombre')->whereColumn('roles.id', 'users.role_id'),
            'creado' => 'created_at',
        ], 'nombre')->paginate(20)->withQueryString();

        return view('usuarios.index', [
            'usuarios' => $usuarios,
            'roles'    => Role::orderBy('nombre')->get(),
        ]);
    }

    public function create(): View
    {
        return view('usuarios.form', [
            'usuario' => new User,
            'roles'   => Role::orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'role_id'  => ['nullable', 'exists:roles,id'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $datos['email_verified_at'] = now();

        User::create($datos);

        return redirect()
            ->route('usuarios.index')
            ->with('success', "Usuario \"{$datos['name']}\" creado correctamente.");
    }

    public function edit(User $usuario): View
    {
        return view('usuarios.form', [
            'usuario' => $usuario,
            'roles'   => Role::orderBy('nombre')->get(),
        ]);
    }

    public function update(Request $request, User $usuario): RedirectResponse
    {
        $datos = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario->id)],
            'role_id'  => ['nullable', 'exists:roles,id'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);

        // Nadie puede dejarse a si mismo sin rol y quedar afuera del sistema.
        if ($usuario->id === Auth::id() && empty($datos['role_id'])) {
            return back()->with('error', 'No podés quitarte el rol a vos mismo.');
        }

        if ($this->dejariaAlSistemaSinAdmin($usuario, $datos['role_id'] ?? null)) {
            return back()->with('error', 'Tiene que quedar al menos un usuario con rol de administrador.');
        }

        if (empty($datos['password'])) {
            unset($datos['password']);
        }

        $usuario->update($datos);

        return redirect()
            ->route('usuarios.index')
            ->with('success', "Usuario \"{$usuario->name}\" actualizado correctamente.");
    }

    public function asignarRol(Request $request, User $usuario): RedirectResponse
    {
        $request->validate([
            'role_id' => ['nullable', 'exists:roles,id'],
        ]);

        $roleId = $request->input('role_id') ?: null;

        if ($usuario->id === Auth::id() && $roleId === null) {
            return back()->with('error', 'No podés quitarte el rol a vos mismo.');
        }

        if ($this->dejariaAlSistemaSinAdmin($usuario, $roleId)) {
            return back()->with('error', 'Tiene que quedar al menos un usuario con rol de administrador.');
        }

        $usuario->role_id = $roleId;
        $usuario->save();

        $nombreRol = $roleId ? Role::find($roleId)?->nombre : 'sin rol';

        return back()->with('success', "Rol de \"{$usuario->name}\" actualizado a \"{$nombreRol}\".");
    }

    public function destroy(User $usuario): RedirectResponse
    {
        if ($usuario->id === Auth::id()) {
            return back()->with('error', 'No podés eliminar tu propia cuenta desde acá.');
        }

        if ($this->dejariaAlSistemaSinAdmin($usuario, null)) {
            return back()->with('error', 'Tiene que quedar al menos un usuario con rol de administrador.');
        }

        $nombre = $usuario->name;
        $usuario->delete();

        return redirect()
            ->route('usuarios.index')
            ->with('success', "Usuario \"{$nombre}\" eliminado.");
    }

    /**
     * True si cambiarle el rol (o borrarlo) deja al sistema sin ningun administrador.
     */
    private function dejariaAlSistemaSinAdmin(User $usuario, ?int $nuevoRoleId): bool
    {
        $idsAdmin = Role::whereIn('nombre', [Role::ADMINISTRADOR, 'admin'])->pluck('id');

        if (! $idsAdmin->contains($usuario->role_id)) {
            return false;                       // no era admin: nada que proteger
        }

        if ($nuevoRoleId !== null && $idsAdmin->contains($nuevoRoleId)) {
            return false;                       // sigue siendo admin
        }

        return User::whereIn('role_id', $idsAdmin)->where('id', '!=', $usuario->id)->doesntExist();
    }
}
