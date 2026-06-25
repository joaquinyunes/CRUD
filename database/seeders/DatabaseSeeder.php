<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::create(['nombre' => 'Administrador']);
        Role::create(['nombre' => 'Supervisor']);
        Role::create(['nombre' => 'Empleado']);
        Role::create(['nombre' => 'Cliente']);

        User::create([
            'name' => 'Admin',
            'email' => 'admin@sistema.local',
            'password' => bcrypt('admin123'),
            'role_id' => $adminRole->id,
            'email_verified_at' => now(),
        ]);

        $this->call([
            PermissionSeeder::class,
        ]);
    }
}
