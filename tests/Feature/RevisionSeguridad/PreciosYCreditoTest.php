<?php

namespace Tests\Feature\RevisionSeguridad;

use App\Models\Cliente;
use App\Models\Permission;
use App\Models\Producto;
use App\Models\Role;
use App\Models\User;
use App\Models\Venta;
use Tests\TestCase;

/**
 * M9: el precio de una venta lo pone el sistema (salvo permiso) y editar una venta
 * respeta el límite de crédito del cliente.
 */
class PreciosYCreditoTest extends TestCase
{
    private function usuarioCon(string ...$claves): User
    {
        $rol = Role::create(['nombre' => 'Rol '.uniqid()]);
        $rol->sincronizarPermisos(collect($claves)->map(fn ($c) => Permission::firstOrCreate(['clave' => $c])->id)->all());
        Role::olvidarClaves($rol->id);

        return User::factory()->create(['role_id' => $rol->id]);
    }

    private function datos(Producto $p, Cliente $c, int $cantidad, float $precio): array
    {
        return [
            'cliente_id' => $c->id,
            'fecha'      => now()->toDateString(),
            'estado'     => 'completada',
            'detalles'   => [['producto_id' => $p->id, 'cantidad' => $cantidad, 'precio' => $precio]],
        ];
    }

    public function test_un_vendedor_sin_permiso_no_cobra_un_precio_distinto_al_de_lista(): void
    {
        $producto = Producto::factory()->create(['stock' => 10, 'precio_venta' => 500]);
        $cliente = Cliente::factory()->create(['limite_credito' => 0]);
        $vendedor = $this->usuarioCon('ventas.ver', 'ventas.crear');
        $jefe = $this->usuarioCon('ventas.ver', 'ventas.crear', 'ventas.precio_libre');

        $this->actingAs($vendedor)->post(route('ventas.store'), $this->datos($producto, $cliente, 1, 1))->assertSessionHasNoErrors();
        $this->assertEquals(500, Venta::latest('id')->first()->detalles()->first()->precio);

        $this->actingAs($jefe)->post(route('ventas.store'), $this->datos($producto, $cliente, 1, 300))->assertSessionHasNoErrors();
        $this->assertEquals(300, Venta::latest('id')->first()->detalles()->first()->precio);
    }

    public function test_la_api_tampoco_acepta_precio_propio_sin_permiso(): void
    {
        $producto = Producto::factory()->create(['stock' => 10, 'precio_venta' => 500]);
        $cliente = Cliente::factory()->create(['limite_credito' => 0]);
        $vendedor = $this->usuarioCon('ventas.ver', 'ventas.crear');

        $this->actingAs($vendedor, 'sanctum')->postJson('/api/ventas', $this->datos($producto, $cliente, 1, 1))->assertSuccessful();

        $this->assertEquals(500, Venta::latest('id')->first()->detalles()->first()->precio);
    }

    public function test_editar_una_venta_no_se_salta_el_limite_de_credito(): void
    {
        $producto = Producto::factory()->create(['stock' => 100, 'precio_venta' => 100]);
        $cliente = Cliente::factory()->create(['limite_credito' => 2000]);
        $jefe = $this->usuarioCon('ventas.ver', 'ventas.crear', 'ventas.editar', 'ventas.precio_libre', 'ventas.gestionar_todas');

        $this->actingAs($jefe)->post(route('ventas.store'), $this->datos($producto, $cliente, 5, 100))->assertSessionHasNoErrors();
        $venta = Venta::firstOrFail();

        // Pasar de 500 a 9.000 supera el límite de 2.000.
        $this->actingAs($jefe)->put(route('ventas.update', $venta), $this->datos($producto, $cliente, 90, 100))->assertSessionHasErrors('cliente_id');
        // Pasar a 1.500 entra: la deuda de esta misma venta no se cuenta dos veces.
        $this->actingAs($jefe)->put(route('ventas.update', $venta), $this->datos($producto, $cliente, 12, 100))->assertSessionHasNoErrors();
    }
}
