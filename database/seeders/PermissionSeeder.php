<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    private array $permisos = [

        'usuarios.ver',
        'usuarios.crear',
        'usuarios.editar',
        'usuarios.eliminar',

        'roles.ver',
        'roles.crear',
        'roles.editar',
        'roles.eliminar',

        'configuracion.ver',
        'configuracion.editar',

        'auditoria.ver',

        'dashboard.ver',

        'productos.ver',
        'productos.crear',
        'productos.editar',
        'productos.eliminar',
        'productos.restaurar',
        'productos.exportar',

        'categorias.ver',
        'categorias.crear',
        'categorias.editar',
        'categorias.eliminar',

        'clientes.ver',
        'clientes.crear',
        'clientes.editar',
        'clientes.eliminar',
        'clientes.exportar',

        'proveedores.ver',
        'proveedores.crear',
        'proveedores.editar',
        'proveedores.eliminar',

        'ventas.ver',
        'ventas.crear',
        'ventas.editar',
        'ventas.eliminar',
        'ventas.exportar',

        'pos.usar',
        'pos.supervisar',

        'promociones.gestionar',

        'compras.ver',
        'compras.crear',
        'compras.editar',
        'compras.eliminar',
        'compras.exportar',

        'stock.ver',
        'stock.ajustar',

        'depositos.ver',
        'depositos.gestionar',

        'cuentas.ver',
        'cuentas.cobrar',

        'presupuestos.ver',
        'presupuestos.crear',
        'presupuestos.editar',
        'presupuestos.eliminar',
        'presupuestos.convertir',

        'ordenes_compra.ver',
        'ordenes_compra.crear',
        'ordenes_compra.editar',
        'ordenes_compra.eliminar',
        'ordenes_compra.recibir',

        'devoluciones.ver',
        'devoluciones.crear',

        'caja.ver',
        'caja.operar',

        'reportes.ver',
        'reportes.exportar',
    ];

    private array $permisosPorRol = [

        Role::SUPERVISOR => [
            'dashboard.ver',
            'productos.ver', 'productos.crear', 'productos.editar', 'productos.exportar',
            'categorias.ver', 'categorias.crear', 'categorias.editar',
            'clientes.ver', 'clientes.crear', 'clientes.editar', 'clientes.exportar',
            'proveedores.ver', 'proveedores.crear', 'proveedores.editar',
            'ventas.ver', 'ventas.crear', 'ventas.editar', 'ventas.exportar',
            'pos.usar', 'pos.supervisar', 'promociones.gestionar',
            'compras.ver', 'compras.crear', 'compras.editar', 'compras.exportar',
            'stock.ver', 'stock.ajustar',
            'depositos.ver', 'depositos.gestionar',
            'cuentas.ver', 'cuentas.cobrar',
            'devoluciones.ver', 'devoluciones.crear',
            'caja.ver', 'caja.operar',
            'presupuestos.ver', 'presupuestos.crear', 'presupuestos.editar', 'presupuestos.convertir',
            'ordenes_compra.ver', 'ordenes_compra.crear', 'ordenes_compra.editar', 'ordenes_compra.recibir',
            'reportes.ver', 'reportes.exportar',
            'auditoria.ver',
        ],

        Role::EMPLEADO => [
            'dashboard.ver',
            'productos.ver',
            'categorias.ver',
            'clientes.ver', 'clientes.crear', 'clientes.editar',
            'ventas.ver', 'ventas.crear', 'ventas.editar', 'ventas.eliminar',
            'pos.usar',
            'compras.ver', 'compras.crear', 'compras.editar',
            'stock.ver',
            'depositos.ver',
            'cuentas.ver', 'cuentas.cobrar',
            'devoluciones.ver', 'devoluciones.crear',
            'caja.ver', 'caja.operar',
            'presupuestos.ver', 'presupuestos.crear', 'presupuestos.editar', 'presupuestos.convertir',
            'ordenes_compra.ver', 'ordenes_compra.crear',
        ],

        Role::CLIENTE => [],
    ];

    public function run(): void
    {
        foreach ($this->permisos as $clave) {
            Permission::firstOrCreate(['clave' => $clave]);
        }

        $admin = Role::where('nombre', Role::ADMINISTRADOR)->first();
        if ($admin) {
            $admin->permissions()->sync(
                Permission::pluck('id')->toArray()
            );
        }

        foreach ($this->permisosPorRol as $nombreRol => $claves) {
            $rol = Role::where('nombre', $nombreRol)->first();
            if (! $rol) {
                continue;
            }
            $ids = Permission::whereIn('clave', $claves)->pluck('id')->toArray();
            $rol->permissions()->sync($ids);
        }
    }
}
