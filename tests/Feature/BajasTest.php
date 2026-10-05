<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Nada que tenga historial se borra: se archiva. Antes, borrar un proveedor con
 * compras o un usuario con ventas llegaba al usuario como un error 500 de la
 * base de datos.
 */
class BajasTest extends TestCase
{
    use RefreshDatabase;

    private function seedRoles(): void
    {
        $this->rolAdministrador();
        Role::create(['nombre' => Role::SUPERVISOR]);
        Role::create(['nombre' => Role::EMPLEADO]);
        Role::create(['nombre' => Role::CLIENTE]);
        $this->seed(PermissionSeeder::class);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role_id' => Role::where('nombre', Role::ADMINISTRADOR)->value('id'),
        ]);
    }

    public function test_un_proveedor_con_compras_se_archiva_en_vez_de_romper(): void
    {
        $this->seedRoles();
        $admin = $this->admin();
        $proveedor = Proveedor::factory()->create();

        Compra::create([
            'numero'       => 'COM-00001',
            'proveedor_id' => $proveedor->id,
            'fecha'        => now()->toDateString(),
            'total'        => 100,
            'total_final'  => 100,
            'estado'       => 'completada',
            'user_id'      => $admin->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('proveedores.destroy', $proveedor))
            ->assertRedirect(route('proveedores.index'));

        $this->assertDatabaseHas('proveedores', [
            'id'     => $proveedor->id,
            'estado' => 'archivado',
        ]);
        $this->assertDatabaseCount('compras', 1);
    }

    public function test_un_proveedor_sin_compras_si_se_borra(): void
    {
        $this->seedRoles();
        $proveedor = Proveedor::factory()->create();

        $this->actingAs($this->admin())->delete(route('proveedores.destroy', $proveedor));

        $this->assertDatabaseMissing('proveedores', ['id' => $proveedor->id]);
    }

    public function test_un_proveedor_archivado_no_aparece_en_el_listado_pero_se_puede_restaurar(): void
    {
        $this->seedRoles();
        $admin = $this->admin();
        $proveedor = Proveedor::factory()->create(['estado' => 'archivado', 'nombre' => 'Archivado SA']);

        $this->actingAs($admin)->get(route('proveedores.index'))->assertDontSee('Archivado SA');
        $this->actingAs($admin)->get(route('proveedores.index', ['archivados' => 1]))->assertSee('Archivado SA');

        $this->actingAs($admin)->post(route('proveedores.restaurar', $proveedor));

        $this->assertDatabaseHas('proveedores', ['id' => $proveedor->id, 'estado' => 'activo']);
    }

    public function test_un_usuario_con_ventas_se_desactiva_en_vez_de_romper(): void
    {
        $this->seedRoles();
        $admin = $this->admin();
        $vendedor = User::factory()->create([
            'role_id' => Role::where('nombre', Role::EMPLEADO)->value('id'),
        ]);

        $this->actingAs($vendedor)->post(route('ventas.store'), [
            'cliente_id' => Cliente::factory()->create()->id,
            'fecha'      => now()->toDateString(),
            'estado'     => 'completada',
            'detalles'   => [['producto_id' => Producto::factory()->create(['stock' => 10])->id, 'cantidad' => 1, 'precio' => 100]],
        ]);

        $this->actingAs($admin)
            ->delete(route('usuarios.destroy', $vendedor))
            ->assertRedirect(route('usuarios.index'));

        $this->assertDatabaseHas('users', ['id' => $vendedor->id, 'activo' => false]);
        $this->assertDatabaseCount('ventas', 1);
    }

    public function test_un_usuario_desactivado_no_puede_entrar(): void
    {
        $this->seedRoles();
        $usuario = User::factory()->desactivado()->create([
            'role_id' => Role::where('nombre', Role::EMPLEADO)->value('id'),
        ]);

        $this->post('/login', ['email' => $usuario->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_desactivar_a_alguien_lo_echa_de_la_sesion_abierta(): void
    {
        $this->seedRoles();
        $usuario = User::factory()->create([
            'role_id' => Role::where('nombre', Role::EMPLEADO)->value('id'),
        ]);

        $this->actingAs($usuario)->get(route('dashboard'))->assertOk();

        $usuario->update(['activo' => false]);

        $this->actingAs($usuario)->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_el_listado_muestra_al_usuario_desactivado_y_permite_reactivarlo(): void
    {
        $this->seedRoles();
        $admin = $this->admin();
        $usuario = User::factory()->desactivado()->create([
            'name'    => 'Empleado De Baja',
            'role_id' => Role::where('nombre', Role::EMPLEADO)->value('id'),
        ]);

        $this->actingAs($admin)->get(route('usuarios.index'))
            ->assertOk()
            ->assertSee('Empleado De Baja')
            ->assertSee('desactivado')
            ->assertSee('Reactivar');

        $this->actingAs($admin)->post(route('usuarios.reactivar', $usuario));

        $this->assertDatabaseHas('users', ['id' => $usuario->id, 'activo' => true]);
    }

    public function test_un_usuario_sin_historial_si_se_borra(): void
    {
        $this->seedRoles();
        $usuario = User::factory()->create([
            'role_id' => Role::where('nombre', Role::EMPLEADO)->value('id'),
        ]);

        $this->actingAs($this->admin())->delete(route('usuarios.destroy', $usuario));

        $this->assertDatabaseMissing('users', ['id' => $usuario->id]);
    }
}
