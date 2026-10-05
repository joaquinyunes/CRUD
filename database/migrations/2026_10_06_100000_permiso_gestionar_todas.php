<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Las políticas de ventas y compras comparaban el nombre del rol ("Administrador",
 * "admin", "Supervisor") para decidir quién actúa sobre comprobantes ajenos. Ahora
 * lo decide un permiso, como todo lo demás.
 *
 * Para que una instalación existente no pierda ese acceso al actualizar, el
 * permiso se crea acá y se les da a los roles que lo tenían de hecho: Supervisor
 * y cualquier rol con el comodín (que ya los cubre sin necesidad de fila).
 */
return new class extends Migration
{
    private const CLAVES = ['ventas.gestionar_todas', 'compras.gestionar_todas', 'ventas.descuento_libre'];

    public function up(): void
    {
        $ids = [];
        foreach (self::CLAVES as $clave) {
            DB::table('permissions')->insertOrIgnore(['clave' => $clave]);
            $ids[] = DB::table('permissions')->where('clave', $clave)->value('id');
        }

        foreach (DB::table('roles')->whereIn('nombre', ['Supervisor'])->pluck('id') as $roleId) {
            foreach ($ids as $permissionId) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'role_id'       => $roleId,
                    'permission_id' => $permissionId,
                ]);
            }

            // El middleware cachea las claves de cada rol: que el cambio se vea ya.
            Role::olvidarClaves((int) $roleId);
        }
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('clave', self::CLAVES)->delete();
    }
};
