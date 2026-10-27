<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

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
        // Roles y permisos viven en MongoDB: se usan los modelos, no tablas SQL.
        $ids = collect(self::CLAVES)
            ->map(fn (string $clave) => Permission::firstOrCreate(['clave' => $clave])->id)
            ->all();

        foreach (Role::whereIn('nombre', ['Supervisor'])->get() as $rol) {
            $rol->permissions()->syncWithoutDetaching($ids);

            // El middleware cachea las claves de cada rol: que el cambio se vea ya.
            Role::olvidarClaves($rol->id);
        }
    }

    public function down(): void
    {
        Permission::whereIn('clave', self::CLAVES)->delete();
    }
};
