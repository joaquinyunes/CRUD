<?php

namespace Tests\Feature\RevisionSeguridad;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

/**
 * H4: quien puede editar roles o usuarios no puede, con eso, darse (ni darle a otro)
 * más poder del que tiene.
 */
class EscaladaDePrivilegiosTest extends TestCase
{
    private function rol(string $nombre, string ...$claves): Role
    {
        $rol = Role::create(['nombre' => $nombre]);
        $rol->sincronizarPermisos(collect($claves)->map(fn ($c) => Permission::firstOrCreate(['clave' => $c])->id)->all());
        Role::olvidarClaves($rol->id);

        return $rol;
    }

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['ventas.ver', 'roles.ver', 'roles.editar', 'usuarios.ver', 'usuarios.crear', 'usuarios.editar', 'usuarios.eliminar', 'backup.gestionar', '*'] as $clave) {
            Permission::firstOrCreate(['clave' => $clave]);
        }
    }

    private function usuario(Role $rol): User
    {
        return User::factory()->create(['role_id' => $rol->id]);
    }

    /** Un «gerente» con permiso de editar roles y usuarios, pero que NO es administrador. */
    private function gerente(): User
    {
        return $this->usuario($this->rol('Gerente', 'roles.ver', 'roles.editar', 'usuarios.ver', 'usuarios.crear', 'usuarios.editar', 'usuarios.eliminar', 'ventas.ver'));
    }

    public function test_un_gerente_no_agrega_permisos_que_no_tiene_a_un_rol(): void
    {
        $gerente = $this->gerente();
        $empleado = $this->rol('Empleado', 'ventas.ver');
        Permission::firstOrCreate(['clave' => 'backup.gestionar']);

        $this->actingAs($gerente)->put(route('roles.update', $empleado), ['permisos' => ['ventas.ver', 'backup.gestionar']])
            ->assertSessionHas('error');

        $this->assertNotContains('backup.gestionar', Role::clavesDe($empleado->id));
    }

    public function test_nadie_que_no_sea_administrador_asigna_el_comodin(): void
    {
        $gerente = $this->gerente();
        $rolDelGerente = Role::find($gerente->role_id);
        Permission::firstOrCreate(['clave' => '*']);

        $this->actingAs($gerente)->put(route('roles.update', $rolDelGerente), ['permisos' => ['roles.editar', '*']])
            ->assertSessionHas('error');

        $this->assertNotContains('*', Role::clavesDe($rolDelGerente->id));
    }

    public function test_el_rol_con_comodin_esta_protegido_aunque_no_se_llame_administrador(): void
    {
        $this->rolAdministrador('Dueño');
        $dueño = Role::where('nombre', 'Dueño')->firstOrFail();
        $admin = $this->usuario($dueño);
        $otroAdmin = $this->rolAdministrador('Socio');

        // Ni el propio administrador reescribe un rol con comodín por la pantalla de roles.
        $this->actingAs($admin)->put(route('roles.update', $otroAdmin), ['permisos' => ['ventas.ver']])
            ->assertSessionHas('error');
        $this->assertContains('*', Role::clavesDe($otroAdmin->id));
    }

    public function test_un_gerente_no_asigna_un_rol_con_mas_poder_que_el_suyo(): void
    {
        $gerente = $this->gerente();
        $admin = $this->rolAdministrador();
        $victima = $this->usuario($this->rol('Cajero', 'ventas.ver'));

        $this->actingAs($gerente)->put(route('usuarios.asignar-rol', $victima), ['role_id' => $admin->id])->assertSessionHas('error');
        $this->actingAs($gerente)->put(route('usuarios.update', $victima), [
            'name' => 'X', 'email' => $victima->email, 'role_id' => $admin->id, 'activo' => 1,
        ])->assertSessionHas('error');
        $this->actingAs($gerente)->post(route('usuarios.store'), [
            'name'     => 'Nuevo', 'email' => 'nuevo@x.com', 'role_id' => $admin->id,
            'password' => 'una-clave-larga-1', 'password_confirmation' => 'una-clave-larga-1',
        ])->assertSessionHas('error');

        $this->assertNotSame($admin->id, $victima->fresh()->role_id);
        $this->assertNull(User::where('email', 'nuevo@x.com')->first());
    }

    public function test_un_gerente_no_toma_la_cuenta_de_un_administrador_cambiandole_la_clave(): void
    {
        $gerente = $this->gerente();
        $admin = $this->usuario($this->rolAdministrador());
        $hashAntes = $admin->password;

        $this->actingAs($gerente)->put(route('usuarios.update', $admin), [
            'name'     => $admin->name, 'email' => 'atacante@x.com', 'role_id' => $admin->role_id, 'activo' => 1,
            'password' => 'clave-del-atacante-1', 'password_confirmation' => 'clave-del-atacante-1',
        ])->assertSessionHas('error');
        $this->actingAs($gerente)->delete(route('usuarios.destroy', $admin))->assertSessionHas('error');

        $this->assertSame($hashAntes, $admin->fresh()->password);
        $this->assertNotSame('atacante@x.com', $admin->fresh()->email);
    }

    public function test_el_administrador_si_puede_hacer_todo_eso(): void
    {
        $admin = $this->usuario($this->rolAdministrador());
        $empleado = $this->rol('Empleado', 'ventas.ver');
        $victima = $this->usuario($this->rol('Cajero', 'ventas.ver'));

        $this->actingAs($admin)->put(route('roles.update', $empleado), ['permisos' => ['ventas.ver', 'roles.ver']])->assertSessionHas('success');
        $this->actingAs($admin)->put(route('usuarios.asignar-rol', $victima), ['role_id' => $empleado->id])->assertSessionHas('success');
        $this->assertSame($empleado->id, $victima->fresh()->role_id);
    }
}
