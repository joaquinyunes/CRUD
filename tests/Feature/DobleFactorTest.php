<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DobleFactorService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * Doble factor por aplicación de autenticación.
 *
 * Lo que importa de verdad: con el doble factor activo, saber la contraseña no
 * alcanza para entrar.
 */
class DobleFactorTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(): User
    {
        $this->seed(PermissionSeeder::class);

        return User::factory()->create(['role_id' => $this->rolAdministrador()->id]);
    }

    private function servicio(): DobleFactorService
    {
        return app(DobleFactorService::class);
    }

    /** Un código válido, como el que mostraría la aplicación del usuario. */
    private function codigoValido(User $usuario): string
    {
        return app(Google2FA::class)->getCurrentOtp($this->servicio()->secretoDe($usuario->fresh()));
    }

    /** Deja al usuario con el doble factor ya confirmado. */
    private function conDobleFactor(): User
    {
        $usuario = $this->usuario();
        $this->servicio()->iniciarAlta($usuario);
        $this->servicio()->confirmar($usuario, $this->codigoValido($usuario));

        return $usuario->fresh();
    }

    public function test_el_alta_no_queda_activa_hasta_confirmar(): void
    {
        $usuario = $this->usuario();

        $this->actingAs($usuario)->post(route('doble-factor.iniciar'))
            ->assertSessionHas('codigos_recuperacion');

        $usuario->refresh();

        $this->assertNotNull($usuario->two_factor_secret);
        $this->assertFalse($usuario->tieneDobleFactor(), 'Escanear mal el QR dejaría al usuario afuera del sistema.');
    }

    public function test_un_codigo_incorrecto_no_confirma_el_alta(): void
    {
        $usuario = $this->usuario();
        $this->servicio()->iniciarAlta($usuario);

        $this->actingAs($usuario)->post(route('doble-factor.confirmar'), ['codigo' => '000000'])
            ->assertSessionHasErrors('codigo');

        $this->assertFalse($usuario->fresh()->tieneDobleFactor());
    }

    public function test_un_codigo_correcto_activa_el_doble_factor(): void
    {
        $usuario = $this->usuario();
        $this->servicio()->iniciarAlta($usuario);

        $this->actingAs($usuario)
            ->post(route('doble-factor.confirmar'), ['codigo' => $this->codigoValido($usuario)])
            ->assertSessionHasNoErrors();

        $this->assertTrue($usuario->fresh()->tieneDobleFactor());
    }

    public function test_con_doble_factor_la_contrasenha_sola_no_abre_sesion(): void
    {
        $usuario = $this->conDobleFactor();

        $this->post('/login', ['email' => $usuario->email, 'password' => 'password'])
            ->assertRedirect(route('doble-factor.desafio'));

        // Saber la contraseña no alcanza: la sesión no se abrió.
        $this->assertGuest();
    }

    public function test_el_codigo_correcto_completa_el_login(): void
    {
        $usuario = $this->conDobleFactor();

        $this->post('/login', ['email' => $usuario->email, 'password' => 'password']);

        $this->post(route('doble-factor.verificar'), ['codigo' => $this->codigoValido($usuario)])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($usuario);
    }

    public function test_un_codigo_incorrecto_no_completa_el_login(): void
    {
        $usuario = $this->conDobleFactor();

        $this->post('/login', ['email' => $usuario->email, 'password' => 'password']);

        $this->post(route('doble-factor.verificar'), ['codigo' => '000000'])
            ->assertSessionHasErrors('codigo');

        $this->assertGuest();
    }

    public function test_sin_pasar_por_el_login_no_se_llega_al_desafio(): void
    {
        $this->get(route('doble-factor.desafio'))->assertRedirect(route('login'));
    }

    public function test_un_codigo_de_recuperacion_entra_y_se_consume(): void
    {
        $usuario = $this->usuario();
        $codigos = $this->servicio()->iniciarAlta($usuario);
        $this->servicio()->confirmar($usuario, $this->codigoValido($usuario));

        $this->post('/login', ['email' => $usuario->email, 'password' => 'password']);
        $this->post(route('doble-factor.verificar'), ['codigo' => $codigos[0]]);

        $this->assertAuthenticatedAs($usuario->fresh());
        $this->assertCount(7, $this->servicio()->codigosDeRecuperacion($usuario->fresh()));

        // El mismo código ya no sirve una segunda vez.
        $this->post(route('logout'));
        $this->post('/login', ['email' => $usuario->email, 'password' => 'password']);
        $this->post(route('doble-factor.verificar'), ['codigo' => $codigos[0]])
            ->assertSessionHasErrors('codigo');
        $this->assertGuest();
    }

    public function test_se_corta_la_fuerza_bruta_sobre_el_codigo(): void
    {
        $usuario = $this->conDobleFactor();

        $this->post('/login', ['email' => $usuario->email, 'password' => 'password']);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('doble-factor.verificar'), ['codigo' => '000000']);
        }

        $this->post(route('doble-factor.verificar'), ['codigo' => '000000'])
            ->assertSessionHasErrors('codigo');

        // Aunque ahora mande el código bueno, tiene que esperar.
        $this->post(route('doble-factor.verificar'), ['codigo' => $this->codigoValido($usuario)]);
        $this->assertGuest();
    }

    public function test_desactivar_exige_la_contrasenha(): void
    {
        $usuario = $this->conDobleFactor();

        $this->actingAs($usuario)->delete(route('doble-factor.desactivar'), ['password' => 'incorrecta'])
            ->assertSessionHasErrors('password', null, 'dobleFactor');

        $this->assertTrue($usuario->fresh()->tieneDobleFactor());

        $this->actingAs($usuario)->delete(route('doble-factor.desactivar'), ['password' => 'password'])
            ->assertSessionHasNoErrors();

        $this->assertFalse($usuario->fresh()->tieneDobleFactor());
        $this->assertNull($usuario->fresh()->two_factor_secret);
    }

    public function test_un_usuario_desactivado_no_pasa_el_desafio(): void
    {
        $usuario = $this->conDobleFactor();

        $this->post('/login', ['email' => $usuario->email, 'password' => 'password']);

        $usuario->update(['activo' => false]);

        $this->post(route('doble-factor.verificar'), ['codigo' => $this->codigoValido($usuario)])
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_el_secreto_no_se_guarda_en_claro(): void
    {
        $usuario = $this->conDobleFactor();

        $guardado = \DB::table('users')->where('id', $usuario->id)->value('two_factor_secret');

        $this->assertNotSame($this->servicio()->secretoDe($usuario), $guardado);
        $this->assertStringNotContainsString($this->servicio()->secretoDe($usuario), (string) $guardado);
    }

    public function test_sin_doble_factor_el_login_sigue_siendo_de_un_paso(): void
    {
        $usuario = $this->usuario();

        $this->post('/login', ['email' => $usuario->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($usuario);
    }
}
