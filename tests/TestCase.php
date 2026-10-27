<?php

namespace Tests;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Concerns\LimpiaMongo;

abstract class TestCase extends BaseTestCase
{
    // Los usuarios, roles y permisos ya viven en MongoDB: todo test arranca con la base vacía.
    use LimpiaMongo;

    /**
     * El rol que puede todo.
     *
     * "Administrador" no es un nombre con poderes especiales: es un rol que
     * tiene el permiso comodín, como cualquier otro rol que alguien cree.
     */
    protected function rolAdministrador(string $nombre = Role::ADMINISTRADOR): Role
    {
        $rol = Role::firstOrCreate(['nombre' => $nombre]);

        // El sync del paquete de MongoDB pide una lista, no un id suelto.
        $rol->permissions()->syncWithoutDetaching(
            [Permission::firstOrCreate(['clave' => Role::TODO])->id]
        );

        Role::olvidarClaves($rol->id);

        return $rol;
    }
}
