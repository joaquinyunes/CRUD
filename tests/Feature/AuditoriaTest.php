<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Role;
use App\Models\User;
use App\Models\Venta;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Cada movimiento queda auditado con el usuario que lo hizo": la auditoría
 * tiene que cubrir los comprobantes y las cuentas, no solo el ABM de catálogo.
 */
class AuditoriaTest extends TestCase
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

    public function test_una_venta_queda_auditada_con_su_autor(): void
    {
        $this->seedRoles();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('ventas.store'), [
            'cliente_id' => Cliente::factory()->create()->id,
            'fecha'      => now()->toDateString(),
            'estado'     => 'completada',
            'detalles'   => [['producto_id' => Producto::factory()->create(['stock' => 10])->id, 'cantidad' => 1, 'precio' => 100]],
        ]);

        $registro = Auditoria::where('modelo_afectado', Venta::class)->where('accion', 'created')->first();

        $this->assertNotNull($registro, 'Crear una venta no dejó rastro en la auditoría.');
        $this->assertSame($admin->id, $registro->user_id);
        $this->assertSame(Venta::latest('id')->first()->id, (int) $registro->modelo_id);
    }

    public function test_anular_una_venta_queda_auditado_con_el_antes_y_el_despues(): void
    {
        $this->seedRoles();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('ventas.store'), [
            'cliente_id' => Cliente::factory()->create()->id,
            'fecha'      => now()->toDateString(),
            'estado'     => 'completada',
            'detalles'   => [['producto_id' => Producto::factory()->create(['stock' => 10])->id, 'cantidad' => 1, 'precio' => 100]],
        ]);

        $venta = Venta::latest('id')->first();
        $this->actingAs($admin)->delete(route('ventas.destroy', $venta));

        $registro = Auditoria::where('modelo_afectado', Venta::class)->where('accion', 'updated')->latest('id')->first();

        $this->assertNotNull($registro);
        $this->assertSame('completada', $registro->valor_anterior['estado']);
        $this->assertSame('anulada', $registro->valor_nuevo['estado']);
    }

    public function test_los_cambios_sobre_usuarios_quedan_auditados_sin_guardar_la_contrasenha(): void
    {
        $this->seedRoles();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('usuarios.store'), [
            'name'                  => 'Nuevo Empleado',
            'email'                 => 'nuevo@demo.com',
            'role_id'               => Role::where('nombre', Role::EMPLEADO)->value('id'),
            'password'              => 'unaClaveLarga123',
            'password_confirmation' => 'unaClaveLarga123',
        ]);

        $registro = Auditoria::where('modelo_afectado', User::class)->where('accion', 'created')->latest('id')->first();

        $this->assertNotNull($registro);
        $this->assertSame('Nuevo Empleado', $registro->valor_nuevo['name']);
        $this->assertArrayNotHasKey('password', $registro->valor_nuevo, 'La auditoría guardó el hash de la contraseña.');
        $this->assertArrayNotHasKey('remember_token', $registro->valor_nuevo);
    }

    public function test_un_cambio_hecho_sin_sesion_tambien_queda_registrado(): void
    {
        $this->seedRoles();

        // Una tarea programada o un comando de consola: no hay usuario, pero el
        // cambio es exactamente el que más interesa poder rastrear.
        $cliente = Cliente::factory()->create();

        $registro = Auditoria::where('modelo_afectado', Cliente::class)->latest('id')->first();

        $this->assertNotNull($registro, 'Un cambio sin usuario autenticado no quedó registrado.');
        $this->assertNull($registro->user_id);
        $this->assertSame($cliente->id, (int) $registro->modelo_id);
    }
}
