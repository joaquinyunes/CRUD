<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstalarSistemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_una_base_vacia_queda_con_permisos_rol_y_administrador(): void
    {
        $this->artisan('sistema:instalar', ['--email' => 'dueno@empresa.com', '--password' => 'una-clave-larga-123'])
            ->assertSuccessful();

        $admin = User::where('email', 'dueno@empresa.com')->firstOrFail();

        $this->assertSame(Role::ADMINISTRADOR, $admin->role->nombre);
        $this->assertTrue($admin->role->tienePermiso('backup.gestionar'));
        $this->assertTrue(Permission::where('clave', Role::TODO)->exists());
        $this->assertNotNull($admin->email_verified_at);
        $this->assertTrue(password_verify('una-clave-larga-123', $admin->password));
    }

    public function test_no_crea_las_cuentas_de_demostracion(): void
    {
        $this->artisan('sistema:instalar', ['--email' => 'dueno@empresa.com', '--password' => 'una-clave-larga-123'])
            ->assertSuccessful();

        $this->assertSame(1, User::count());
        $this->assertFalse(User::where('email', 'admin@admin.com')->exists());
    }

    public function test_correrlo_otra_vez_no_toca_la_contrasena_ni_duplica(): void
    {
        $this->artisan('sistema:instalar', ['--email' => 'dueno@empresa.com', '--password' => 'una-clave-larga-123']);
        $this->artisan('sistema:instalar', ['--email' => 'dueno@empresa.com', '--password' => 'otra-clave-distinta-9'])
            ->assertSuccessful();

        $this->assertSame(1, User::count());
        $this->assertTrue(password_verify('una-clave-larga-123', User::firstOrFail()->password));
    }

    public function test_un_arranque_posterior_sin_variables_no_falla_ni_borra_nada(): void
    {
        $this->artisan('sistema:instalar', ['--email' => 'dueno@empresa.com', '--password' => 'una-clave-larga-123']);

        $this->artisan('sistema:instalar', ['--no-interaction' => true])->assertSuccessful();

        $this->assertSame(1, User::count());
    }

    public function test_un_permiso_nuevo_llega_al_administrador_en_el_siguiente_arranque(): void
    {
        $this->artisan('sistema:instalar', ['--email' => 'dueno@empresa.com', '--password' => 'una-clave-larga-123']);
        $admin = Role::where('nombre', Role::ADMINISTRADOR)->firstOrFail();
        $admin->permissions()->detach();
        Role::olvidarClaves($admin->id);

        $this->artisan('sistema:instalar', ['--no-interaction' => true])->assertSuccessful();

        $this->assertTrue($admin->fresh()->tienePermiso('ventas.ver'));
    }

    public function test_no_pisa_los_permisos_que_el_cliente_le_dio_a_otro_rol(): void
    {
        $this->artisan('sistema:instalar', ['--email' => 'dueno@empresa.com', '--password' => 'una-clave-larga-123']);
        $rol = Role::create(['nombre' => 'Contador']);
        $rol->permissions()->sync([Permission::where('clave', 'reportes.ver')->value('id')]);

        $this->artisan('sistema:instalar', ['--no-interaction' => true])->assertSuccessful();

        $this->assertSame(['reportes.ver'], $rol->permissions()->pluck('clave')->all());
    }

    public function test_rechaza_una_contrasena_corta_y_no_crea_la_cuenta(): void
    {
        $this->artisan('sistema:instalar', ['--email' => 'dueno@empresa.com', '--password' => 'corta'])
            ->assertFailed();

        $this->assertSame(0, User::count());
    }

    public function test_sin_variables_ni_terminal_avisa_pero_no_falla_el_arranque(): void
    {
        $this->artisan('sistema:instalar', ['--no-interaction' => true])
            ->expectsOutputToContain('nadie va a poder entrar')
            ->assertSuccessful();
    }

    public function test_toma_la_cuenta_de_la_configuracion(): void
    {
        config([
            'sistema.admin.email'    => 'env@empresa.com',
            'sistema.admin.password' => 'clave-de-entorno-123',
            'sistema.admin.nombre'   => 'Dueña',
        ]);

        $this->artisan('sistema:instalar', ['--no-interaction' => true])->assertSuccessful();

        $this->assertSame('Dueña', User::where('email', 'env@empresa.com')->firstOrFail()->name);
    }
}
