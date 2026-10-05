<?php

namespace Tests;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * El rol que puede todo.
     *
     * "Administrador" no es un nombre con poderes especiales: es un rol que
     * tiene el permiso comodín, como cualquier otro rol que alguien cree.
     */
    protected function rolAdministrador(string $nombre = Role::ADMINISTRADOR): Role
    {
        $rol = Role::firstOrCreate(['nombre' => $nombre]);

        $rol->permissions()->syncWithoutDetaching(
            Permission::firstOrCreate(['clave' => Role::TODO])->id
        );

        Role::olvidarClaves($rol->id);

        return $rol;
    }
}
