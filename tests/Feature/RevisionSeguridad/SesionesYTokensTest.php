<?php

namespace Tests\Feature\RevisionSeguridad;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * M7: cambiar la contraseña corta las sesiones abiertas en otros equipos y revoca los
 * tokens de la API; los tokens vencen.
 */
class SesionesYTokensTest extends TestCase
{
    public function test_cambiar_la_contrasenha_invalida_una_sesion_abierta_en_otro_equipo(): void
    {
        $usuario = User::factory()->create(['role_id' => $this->rolAdministrador()->id]);
        $hashViejo = $usuario->getAuthPassword();

        // «Otro equipo»: una sesión que guardó el hash de la contraseña de antes.
        $otroEquipo = $this->actingAs($usuario)->withSession(['password_hash_web' => $hashViejo]);
        $otroEquipo->get(route('dashboard'))->assertOk();

        $usuario->forceFill(['password' => Hash::make('contrasenha-nueva-123')])->save();

        $this->actingAs($usuario->fresh())->withSession(['password_hash_web' => $hashViejo])
            ->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_cambiar_la_contrasenha_revoca_los_tokens_y_mantiene_la_sesion_actual(): void
    {
        $usuario = User::factory()->create(['role_id' => $this->rolAdministrador()->id]);
        $usuario->createToken('movil');
        $this->assertSame(1, $usuario->tokens()->count());

        $this->actingAs($usuario)->withSession(['password_hash_web' => $usuario->getAuthPassword()])
            ->put(route('password.update'), [
                'current_password'      => 'password',
                'password'              => 'contrasenha-nueva-123',
                'password_confirmation' => 'contrasenha-nueva-123',
            ])->assertSessionHasNoErrors();

        $this->assertSame(0, $usuario->fresh()->tokens()->count());
    }

    public function test_los_tokens_de_la_api_vencen(): void
    {
        $this->assertGreaterThan(0, (int) config('sanctum.expiration'));
        $this->assertLessThanOrEqual(1440, (int) config('sanctum.expiration'));
    }
}
