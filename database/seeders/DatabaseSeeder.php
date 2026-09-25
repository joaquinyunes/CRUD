<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Roles de demo y sus cuentas.
     *
     * - admin         : acceso total (el middleware lo deja pasar por nombre).
     * - Solo Lectura  : ve todo, no modifica nada. Los botones de alta/edicion/baja
     *                   ni siquiera se dibujan porque las vistas consultan permisos.
     * - Vendedor      : opera el dia a dia (ventas, clientes, caja, stock) y edita.
     */
    public function run(): void
    {
        // 1. Catalogo completo de permisos (+ roles Supervisor/Empleado/Cliente si existen).
        $this->call(PermissionSeeder::class);

        $claves = Permission::pluck('id', 'clave');

        // 2. Administrador: todo.
        $admin = Role::firstOrCreate(['nombre' => 'admin']);
        $admin->permissions()->sync($claves->values()->all());

        // 3. Solo Lectura: cualquier permiso de consulta o exportacion, nada que escriba.
        $soloLectura = Role::firstOrCreate(['nombre' => Role::SOLO_LECTURA]);
        $soloLectura->permissions()->sync(
            $claves->filter(fn ($id, $clave) => str_ends_with($clave, '.ver') || str_ends_with($clave, '.exportar'))
                ->values()
                ->all()
        );

        // 4. Vendedor: el rol operativo. Ve todo lo suyo y lo puede modificar.
        $vendedor = Role::firstOrCreate(['nombre' => Role::VENDEDOR]);
        $vendedor->permissions()->sync(
            $claves->only([
                'dashboard.ver',
                'productos.ver', 'productos.crear', 'productos.editar', 'productos.exportar',
                'categorias.ver', 'categorias.crear', 'categorias.editar',
                'clientes.ver', 'clientes.crear', 'clientes.editar', 'clientes.exportar',
                'proveedores.ver', 'proveedores.crear', 'proveedores.editar',
                'ventas.ver', 'ventas.crear', 'ventas.editar', 'ventas.exportar',
                'compras.ver', 'compras.crear', 'compras.editar', 'compras.exportar',
                'presupuestos.ver', 'presupuestos.crear', 'presupuestos.editar', 'presupuestos.convertir',
                'ordenes_compra.ver', 'ordenes_compra.crear', 'ordenes_compra.editar', 'ordenes_compra.recibir',
                'devoluciones.ver', 'devoluciones.crear',
                'stock.ver', 'stock.ajustar',
                'depositos.ver', 'depositos.gestionar',
                'cuentas.ver', 'cuentas.cobrar',
                'caja.ver', 'caja.operar',
                'reportes.ver', 'reportes.exportar',
                'archivos.ver', 'archivos.gestionar',
                'tareas.ver', 'tareas.gestionar',
                'calendario.ver', 'calendario.gestionar',
            ])->values()->all()
        );

        // Compatibilidad: el rol minusculo "vendedor" de versiones previas queda sin uso.
        Role::where('nombre', 'vendedor')->whereDoesntHave('users')->delete();

        // 5. Cuentas de demo (idempotentes: no pisan la contrasena si ya existen).
        $this->crearUsuario('Admin', 'admin@admin.com', 'password', $admin->id);
        $this->crearUsuario('Usuario de consulta', 'lectura@demo.com', 'lectura1234', $soloLectura->id);
        $this->crearUsuario('Vendedor demo', 'vendedor@demo.com', 'vendedor1234', $vendedor->id);

        // 6. Datos base sin los que el sistema arranca vacio.
        $this->call(UniversalSeeder::class);
    }

    private function crearUsuario(string $nombre, string $email, string $password, int $roleId): void
    {
        User::firstOrCreate(
            ['email' => $email],
            [
                'name'              => $nombre,
                'password'          => bcrypt($password),
                'role_id'           => $roleId,
                'email_verified_at' => now(),
            ]
        );
    }
}
