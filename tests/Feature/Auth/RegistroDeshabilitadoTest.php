<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * En un sistema administrativo no hay registro público: las altas de usuario
 * las hace un administrador desde el módulo de usuarios y roles. Estos tests
 * fijan esa decisión para que nadie reponga las rutas de Breeze sin querer.
 */
class RegistroDeshabilitadoTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_pantalla_de_registro_no_existe(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_no_se_puede_crear_un_usuario_desde_afuera(): void
    {
        $this->post('/register', [
            'name'                  => 'Intruso',
            'email'                 => 'intruso@example.com',
            'password'              => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'intruso@example.com']);
    }

    public function test_el_login_sigue_disponible(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_un_usuario_existente_puede_entrar(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email'    => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
    }
}
