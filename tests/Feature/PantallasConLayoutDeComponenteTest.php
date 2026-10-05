<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dos pantallas usan el layout como componente (<x-app-layout>) en lugar de
 * extenderlo, y el layout solo renderizaba @yield('content'): salían
 * completamente en blanco, con un 200 que no delataba nada.
 *
 * Los tests que existían solo verificaban el código de respuesta, así que el
 * perfil del usuario y el editor de permisos estuvieron vacíos sin que nada
 * fallara. Acá se verifica que el contenido esté.
 */
class PantallasConLayoutDeComponenteTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(PermissionSeeder::class);

        return User::factory()->create(['role_id' => $this->rolAdministrador()->id]);
    }

    public function test_el_perfil_muestra_sus_formularios(): void
    {
        $this->actingAs($this->admin())->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Doble factor de autenticación')
            ->assertSee('name="password"', false)
            ->assertSee('name="email"', false);
    }

    public function test_la_edicion_de_permisos_muestra_los_permisos(): void
    {
        $admin = $this->admin();
        $rol = Role::create(['nombre' => 'Depósito']);

        $this->actingAs($admin)->get(route('roles.edit', $rol))
            ->assertOk()
            ->assertSee('Editar permisos')
            ->assertSee('ventas.crear');
    }
}
