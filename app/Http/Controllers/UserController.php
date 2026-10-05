<?php

namespace App\Http\Controllers;

use App\Http\Requests\UsuarioRequest;
use App\Models\Role;
use App\Models\User;
use App\Support\Orden;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    public function store(UsuarioRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        // Los usuarios los da de alta un administrador, no se auto-registran:
        // no tiene sentido mandarles a verificar el correo.
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

    public function update(UsuarioRequest $request, User $usuario): RedirectResponse
    {
        $datos = $request->validated();

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

    /**
     * Un usuario que ya operó no se borra: se desactiva.
     *
     * Sus ventas, compras, movimientos de stock y sesiones de caja lo
     * referencian, y la auditoría necesita saber quién hizo cada cosa. Borrarlo
     * fallaba con un error de base de datos apenas tenía un comprobante encima.
     */
    public function destroy(User $usuario): RedirectResponse
    {
        if ($usuario->id === Auth::id()) {
            return back()->with('error', 'No podés eliminar tu propia cuenta desde acá.');
        }

        if ($this->dejariaAlSistemaSinAdmin($usuario, null)) {
            return back()->with('error', 'Tiene que quedar al menos un usuario con rol de administrador.');
        }

        if ($usuario->tieneHistorial()) {
            $usuario->update(['activo' => false]);
            $usuario->tokens()->delete();

            return redirect()->route('usuarios.index')->with(
                'success',
                "Usuario \"{$usuario->name}\" desactivado: ya no puede entrar al sistema, ".
                'y sus comprobantes siguen en el historial con su nombre.'
            );
        }

        $nombre = $usuario->name;
        $usuario->tokens()->delete();
        $usuario->delete();

        return redirect()
            ->route('usuarios.index')
            ->with('success', "Usuario \"{$nombre}\" eliminado.");
    }

    /** Vuelve a habilitar la entrada de un usuario desactivado. */
    public function reactivar(User $usuario): RedirectResponse
    {
        $usuario->update(['activo' => true]);

        return redirect()
            ->route('usuarios.index')
            ->with('success', "Usuario \"{$usuario->name}\" reactivado.");
    }

    /**
     * True si cambiarle el rol (o darlo de baja) deja al sistema sin nadie que
     * pueda administrarlo.
     *
     * Se mide por el permiso comodín, no por el nombre del rol: un rol que
     * alguien creó y llamó "Dueño" cuenta igual que "Administrador".
     */
    private function dejariaAlSistemaSinAdmin(User $usuario, ?int $nuevoRoleId): bool
    {
        $idsAdmin = Role::whereHas('permissions', fn ($q) => $q->where('clave', Role::TODO))->pluck('id');

        if (! $idsAdmin->contains($usuario->role_id)) {
            return false;                       // no era admin: nada que proteger
        }

        if ($nuevoRoleId !== null && $idsAdmin->contains($nuevoRoleId)) {
            return false;                       // sigue siendo admin
        }

        return User::whereIn('role_id', $idsAdmin)
            ->where('id', '!=', $usuario->id)
            ->where('activo', true)
            ->doesntExist();
    }
}
