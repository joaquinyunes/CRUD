<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RutaRaizTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_invitado_que_entra_a_la_raiz_termina_en_el_login(): void
    {
        $this->get('/')
            ->assertRedirect(route('dashboard'));

        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_un_usuario_autenticado_entra_al_dashboard(): void
    {
        $user = User::factory()->create([
            'role_id' => Role::create(['nombre' => 'Administrador'])->id,
        ]);

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route('dashboard'));

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }
}
