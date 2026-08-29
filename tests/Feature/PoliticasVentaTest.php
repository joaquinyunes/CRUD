<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Role;
use App\Models\User;
use App\Models\Venta;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PoliticasVentaTest extends TestCase
{
    use RefreshDatabase;

    private function seedRoles(): void
    {
        Role::create(['nombre' => 'Administrador']);
        Role::create(['nombre' => 'Supervisor']);
        Role::create(['nombre' => 'Empleado']);
        Role::create(['nombre' => 'Cliente']);
        $this->seed(PermissionSeeder::class);
    }

    private function empleado(): User
    {
        return User::factory()->create(['role_id' => Role::where('nombre', 'Empleado')->value('id')]);
    }

    private function crearVenta(User $u, Producto $p, Cliente $c): Venta
    {
        $this->actingAs($u)->post(route('ventas.store'), [
            'cliente_id' => $c->id,
            'fecha'      => now()->toDateString(),
            'estado'     => 'pendiente',
            'detalles'   => [['producto_id' => $p->id, 'cantidad' => 1, 'precio' => 100]],
        ]);

        return Venta::latest('id')->first();
    }

    public function test_empleado_no_edita_venta_de_otro(): void
    {
        $this->seedRoles();
        $producto = Producto::factory()->create(['stock' => 20]);
        $cliente = Cliente::factory()->create();

        $ana = $this->empleado();
        $beto = $this->empleado();

        $ventaDeAna = $this->crearVenta($ana, $producto, $cliente);

        $this->actingAs($beto)->get(route('ventas.edit', $ventaDeAna))->assertForbidden();
        $this->actingAs($ana)->get(route('ventas.edit', $ventaDeAna))->assertOk();
    }

    public function test_supervisor_edita_venta_de_cualquiera(): void
    {
        $this->seedRoles();
        $producto = Producto::factory()->create(['stock' => 20]);
        $cliente = Cliente::factory()->create();

        $ana = $this->empleado();
        $ventaDeAna = $this->crearVenta($ana, $producto, $cliente);

        $supervisor = User::factory()->create(['role_id' => Role::where('nombre', 'Supervisor')->value('id')]);
        $this->actingAs($supervisor)->get(route('ventas.edit', $ventaDeAna))->assertOk();
    }
}
