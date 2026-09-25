<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Deposito;
use App\Models\OrdenCompra;
use App\Models\Presupuesto;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PantallasFase3Test extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role_id' => Role::create(['nombre' => 'Administrador'])->id]);
    }

    public function test_pantallas_fase3_renderizan(): void
    {
        $admin = $this->admin();
        $producto = Producto::factory()->create(['stock' => 40]);
        $cliente = Cliente::factory()->create();
        $prov = Proveedor::create(['nombre' => 'P', 'cuit' => '30111111118']);

        $this->actingAs($admin)->post(route('presupuestos.store'), [
            'cliente_id' => $cliente->id, 'fecha' => now()->toDateString(),
            'detalles'   => [['producto_id' => $producto->id, 'cantidad' => 2, 'precio' => 100]],
        ]);
        $this->actingAs($admin)->post(route('ordenes-compra.store'), [
            'proveedor_id' => $prov->id, 'fecha' => now()->toDateString(),
            'detalles'     => [['producto_id' => $producto->id, 'cantidad' => 5, 'precio' => 80]],
        ]);
        $sucursal = Deposito::create(['nombre' => 'Sucursal', 'activo' => true]);

        $this->actingAs($admin)->get(route('presupuestos.index'))->assertOk();
        $this->actingAs($admin)->get(route('presupuestos.create'))->assertOk();
        $this->actingAs($admin)->get(route('presupuestos.show', Presupuesto::first()))->assertOk();
        $this->actingAs($admin)->get(route('ordenes-compra.index'))->assertOk();
        $this->actingAs($admin)->get(route('ordenes-compra.create'))->assertOk();
        $this->actingAs($admin)->get(route('ordenes-compra.show', OrdenCompra::first()))->assertOk();
        $this->actingAs($admin)->get(route('ordenes-compra.recibir.form', OrdenCompra::first()))->assertOk();
        $this->actingAs($admin)->get(route('depositos.index'))->assertOk();
        $this->actingAs($admin)->get(route('depositos.create'))->assertOk();
        $this->actingAs($admin)->get(route('depositos.stock', $sucursal))->assertOk();
        $this->actingAs($admin)->get(route('depositos.transferir.form'))->assertOk();
        $this->actingAs($admin)->get(route('ventas.create'))->assertOk();
        $this->actingAs($admin)->get(route('compras.create'))->assertOk();
    }
}
