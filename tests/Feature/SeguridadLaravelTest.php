<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Role;
use App\Models\User;
use App\Models\Venta;
use App\Services\DobleFactorService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Http\UploadedFile;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * Paquete S de seguridad aplicado a Laravel (S3, S4, S5, S7, S9).
 */
class SeguridadLaravelTest extends TestCase
{
    private function roles(): void
    {
        $this->rolAdministrador();
        Role::create(['nombre' => Role::SUPERVISOR]);
        Role::create(['nombre' => Role::EMPLEADO]);
        $this->seed(PermissionSeeder::class);
    }

    private function empleado(): User
    {
        return User::factory()->create(['role_id' => Role::where('nombre', Role::EMPLEADO)->value('id')]);
    }

    private function administrador(): User
    {
        return User::factory()->create(['role_id' => Role::where('nombre', Role::ADMINISTRADOR)->value('id')]);
    }

    private function vender(User $u, Producto $p, Cliente $c): Venta
    {
        $this->actingAs($u)->post(route('ventas.store'), [
            'cliente_id' => $c->id,
            'fecha'      => now()->toDateString(),
            'estado'     => 'pendiente',
            'detalles'   => [['producto_id' => $p->id, 'cantidad' => 1, 'precio' => 100]],
        ])->assertSessionHasNoErrors();

        return Venta::latest('id')->first();
    }

    // ---- S7: un vendedor no ve lo de otro --------------------------------

    public function test_un_vendedor_no_ve_las_ventas_de_otro_pero_el_supervisor_si(): void
    {
        $this->roles();
        $producto = Producto::factory()->create(['stock' => 20]);
        $cliente = Cliente::factory()->create();
        $ana = $this->empleado();
        $beto = $this->empleado();
        $supervisor = User::factory()->create(['role_id' => Role::where('nombre', Role::SUPERVISOR)->value('id')]);

        $ventaDeAna = $this->vender($ana, $producto, $cliente);

        $this->actingAs($beto)->get(route('ventas.show', $ventaDeAna))->assertForbidden();
        $this->actingAs($beto)->get(route('pdf.venta', $ventaDeAna->id))->assertForbidden();
        $this->actingAs($beto)->get(route('ventas.index'))->assertOk()->assertDontSee($ventaDeAna->numero);

        $this->actingAs($ana)->get(route('ventas.show', $ventaDeAna))->assertOk();
        $this->actingAs($ana)->get(route('ventas.index'))->assertSee($ventaDeAna->numero);
        $this->actingAs($supervisor)->get(route('ventas.show', $ventaDeAna))->assertOk();
    }

    public function test_la_api_tampoco_deja_ver_ni_anular_ventas_ajenas(): void
    {
        $this->roles();
        $producto = Producto::factory()->create(['stock' => 20]);
        $cliente = Cliente::factory()->create();
        $ana = $this->empleado();
        $beto = $this->empleado();
        $ventaDeAna = $this->vender($ana, $producto, $cliente);

        $this->actingAs($beto, 'sanctum');
        $this->getJson("/api/ventas/{$ventaDeAna->id}")->assertForbidden();
        $this->deleteJson("/api/ventas/{$ventaDeAna->id}")->assertForbidden();
        // La búsqueda no puede saltarse el filtro de visibilidad (un orWhere suelto lo haría).
        $this->getJson('/api/ventas?buscar='.$ventaDeAna->numero)->assertOk()->assertJsonCount(0, 'data');
        $this->assertNotSame('anulada', $ventaDeAna->fresh()->estado);
    }

    // ---- S7: auditoría que nadie puede borrar -----------------------------

    public function test_la_auditoria_no_se_puede_modificar_ni_borrar(): void
    {
        $linea = Auditoria::create([
            'user_id'         => 1, 'ip' => '127.0.0.1', 'accion' => 'created',
            'modelo_afectado' => 'Producto', 'modelo_id' => 1, 'created_at' => now(),
        ]);

        try {
            $linea->update(['accion' => 'deleted']);
            $this->fail('Se pudo modificar una línea de auditoría.');
        } catch (\LogicException) {
            $this->assertSame('created', $linea->fresh()->accion);
        }

        $this->expectException(\LogicException::class);
        $linea->delete();
    }

    // ---- S5: validación en el borde ---------------------------------------

    public function test_los_operadores_de_mongo_en_el_login_de_la_api_se_rechazan(): void
    {
        User::factory()->create(['email' => 'a@b.com']);

        $this->postJson('/api/login', ['email' => ['$ne' => null], 'password' => ['$ne' => null]])
            ->assertStatus(422);
        $this->postJson('/api/login', ['email' => 'a@b.com', 'password' => ['$ne' => null]])
            ->assertStatus(422);
    }

    public function test_un_archivo_con_extension_de_imagen_pero_contenido_php_no_sube(): void
    {
        $this->roles();
        // Un UploadedFile real (no el fake, que deduce el tipo del nombre): se detecta por contenido.
        $ruta = tempnam(sys_get_temp_dir(), 'seg');
        file_put_contents($ruta, '<?php system($_GET["c"]); ?>');
        $falso = new UploadedFile($ruta, 'foto.jpg', 'image/jpeg', null, true);

        $this->actingAs($this->administrador())
            ->post(route('archivos.store'), ['archivo' => $falso])
            ->assertSessionHasErrors('archivo');
    }

    public function test_la_contrasenha_de_un_usuario_nuevo_pide_12_caracteres(): void
    {
        $this->roles();
        $admin = $this->administrador();
        $base = ['name' => 'X', 'email' => 'x@x.com', 'role_id' => null, 'activo' => 1];

        $this->actingAs($admin)->post(route('usuarios.store'), $base + ['password' => 'corta1234', 'password_confirmation' => 'corta1234'])
            ->assertSessionHasErrors('password');
        $this->actingAs($admin)->post(route('usuarios.store'), $base + ['password' => 'una-clave-larga-1', 'password_confirmation' => 'una-clave-larga-1'])
            ->assertSessionHasNoErrors();
    }

    // ---- S3: límites de intentos -------------------------------------------

    public function test_el_login_de_la_api_se_bloquea_tras_cinco_intentos(): void
    {
        $usuario = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', ['email' => $usuario->email, 'password' => 'mal'])->assertStatus(422);
        }

        $this->postJson('/api/login', ['email' => $usuario->email, 'password' => 'password'])->assertStatus(429);
    }

    public function test_el_login_web_se_bloquea_tras_cinco_intentos(): void
    {
        $usuario = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $usuario->email, 'password' => 'mal']);
        }

        $this->post('/login', ['email' => $usuario->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    // ---- S4: doble factor del administrador -------------------------------

    public function test_en_produccion_el_administrador_sin_doble_factor_solo_llega_al_perfil(): void
    {
        $this->roles();
        config(['sistema.doble_factor_admin_obligatorio' => true]);
        $admin = $this->administrador();

        $this->actingAs($admin)->get(route('dashboard'))->assertRedirect(route('profile.edit'));
        $this->actingAs($admin)->get(route('profile.edit'))->assertOk();
    }

    public function test_la_api_no_da_token_a_un_administrador_sin_doble_factor_ni_con_codigo_malo(): void
    {
        $this->roles();
        config(['sistema.doble_factor_admin_obligatorio' => true]);
        $admin = $this->administrador();

        $this->postJson('/api/login', ['email' => $admin->email, 'password' => 'password'])->assertForbidden();

        $servicio = app(DobleFactorService::class);
        $servicio->iniciarAlta($admin);
        $servicio->confirmar($admin, app(Google2FA::class)->getCurrentOtp($servicio->secretoDe($admin->fresh())));

        $this->postJson('/api/login', ['email' => $admin->email, 'password' => 'password'])->assertStatus(422);
        $this->postJson('/api/login', [
            'email'    => $admin->email,
            'password' => 'password',
            'codigo'   => app(Google2FA::class)->getCurrentOtp($servicio->secretoDe($admin->fresh())),
        ])->assertOk()->assertJsonStructure(['token']);
    }

    // ---- S9: CORS cerrado, cookies y producción ---------------------------

    public function test_cors_no_autoriza_origenes_ajenos(): void
    {
        $respuesta = $this->call('OPTIONS', '/api/login', [], [], [], [
            'HTTP_ORIGIN'                        => 'https://sitio-malo.example',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);

        $this->assertNull($respuesta->headers->get('Access-Control-Allow-Origin'));
    }

    public function test_la_configuracion_de_produccion_es_segura(): void
    {
        $render = file_get_contents(base_path('render.yaml'));

        $this->assertMatchesRegularExpression('/key: APP_DEBUG\s+value: "false"/', $render);
        $this->assertMatchesRegularExpression('/key: APP_ENV\s+value: production/', $render);
        $this->assertMatchesRegularExpression('/key: SESSION_SECURE_COOKIE[^\n]*\n\s+value: "true"/', $render);

        $this->assertTrue(config('session.http_only'));
        $this->assertSame('lax', config('session.same_site'));
        $this->assertNotContains('*', config('cors.allowed_origins'));
        $this->assertFalse(config('cors.supports_credentials'));
    }
}
