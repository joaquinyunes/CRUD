<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $permisos = [
            'productos.ver', 'productos.crear', 'productos.editar', 'productos.eliminar',
            'categorias.ver', 'categorias.crear', 'categorias.editar', 'categorias.eliminar',
            'clientes.ver', 'clientes.crear', 'clientes.editar', 'clientes.eliminar',
            'ventas.ver', 'ventas.crear', 'ventas.editar', 'ventas.eliminar',
            'compras.ver', 'compras.crear', 'compras.editar', 'compras.eliminar',
            'proveedores.ver', 'proveedores.crear', 'proveedores.editar', 'proveedores.eliminar',
            'stock.ver', 'reportes.ver', 'auditoria.ver', 'roles.ver', 'usuarios.ver',
        ];

        foreach ($permisos as $p) {
            Permission::firstOrCreate(['clave' => $p]);
        }

        $admin = Role::firstOrCreate(['nombre' => 'admin']);
        $admin->permissions()->sync(Permission::all()->pluck('id'));

        $vendedor = Role::firstOrCreate(['nombre' => 'vendedor']);
        $vendedor->permissions()->sync(
            Permission::whereIn('clave', ['productos.ver', 'clientes.ver', 'ventas.ver', 'ventas.crear', 'stock.ver'])->pluck('id')
        );

        User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name'     => 'Admin',
                'password' => bcrypt('password'),
                'role_id'  => $admin->id,
            ]
        );
    }
}
