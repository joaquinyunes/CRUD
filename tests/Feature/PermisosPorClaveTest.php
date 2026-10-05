<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Los permisos se resuelven por clave. Ningún nombre de rol tiene poderes
 * especiales escritos en el código.
 */
class PermisosPorClaveTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioCon(string $nombreRol, array $claves): User
    {
        $this->seed(PermissionSeeder::class);

        $rol = Role::firstOrCreate(['nombre' => $nombreRol]);
        $rol->permissions()->sync(Permission::whereIn('clave', $claves)->pluck('id'));
        Role::olvidarClaves($rol->id);

        return User::factory()->create(['role_id' => $rol->id]);
    }

    public function test_un_rol_inventado_con_el_comodin_puede_todo(): void
    {
        $duenho = $this->usuarioCon('Dueño', [Role::TODO]);

        $this->actingAs($duenho)->get(route('usuarios.index'))->assertOk();
        $this->actingAs($duenho)->get(route('auditoria.index'))->assertOk();
    }

    public function test_un_rol_llamado_administrador_sin_permisos_no_puede_nada(): void
    {
        $impostor = $this->usuarioCon(Role::ADMINISTRADOR, []);

        $this->actingAs($impostor)->get(route('usuarios.index'))->assertForbidden();
    }

    public function test_un_rol_llega_hasta_donde_llegan_sus_claves(): void
    {
        $usuario = $this->usuarioCon('Consulta', ['ventas.ver', 'dashboard.ver']);

        $this->actingAs($usuario)->get(route('ventas.index'))->assertOk();
        $this->actingAs($usuario)->get(route('ventas.create'))->assertForbidden();
        $this->actingAs($usuario)->get(route('usuarios.index'))->assertForbidden();
    }

    public function test_cambiar_los_permisos_de_un_rol_se_aplica_enseguida(): void
    {
        $usuario = $this->usuarioCon('Operador', ['ventas.ver']);

        $this->actingAs($usuario)->get(route('ventas.index'))->assertOk();

        // Sin invalidar la caché de permisos, este cambio tardaría una hora.
        $usuario->role->sincronizarPermisos([]);

        $this->actingAs($usuario)->get(route('ventas.index'))->assertForbidden();
    }
}
