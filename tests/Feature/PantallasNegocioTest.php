<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Role;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PantallasNegocioTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role_id' => Role::create(['nombre' => 'Administrador'])->id]);
    }

    public function test_pantallas_nuevas_renderizan(): void
    {
        $admin = $this->admin();
        $cliente = Cliente::factory()->create();
        $producto = Producto::factory()->create(['stock' => 50]);

        $this->actingAs($admin)->post(route('ventas.store'), [
            'cliente_id' => $cliente->id,
            'fecha'      => now()->toDateString(),
            'estado'     => 'completada',
            'detalles'   => [['producto_id' => $producto->id, 'cantidad' => 2, 'precio' => 100]],
        ]);
        $venta = Venta::first();

        $proveedor = Proveedor::create(['nombre' => 'Prov', 'cuit' => '30111111118']);
        $this->actingAs($admin)->post(route('compras.store'), [
            'proveedor_id' => $proveedor->id,
            'fecha'        => now()->toDateString(),
            'estado'       => 'completada',
            'detalles'     => [['producto_id' => $producto->id, 'cantidad' => 5, 'precio' => 80]],
        ]);
        $compra = Compra::first();

        $this->actingAs($admin)->get(route('cuentas.clientes'))->assertOk();
        $this->actingAs($admin)->get(route('cuentas.cliente', $cliente))->assertOk();
        $this->actingAs($admin)->get(route('cuentas.proveedores'))->assertOk();
        $this->actingAs($admin)->get(route('cuentas.proveedor', $proveedor))->assertOk();
        $this->actingAs($admin)->get(route('caja.index'))->assertOk();
        $this->actingAs($admin)->get(route('devoluciones.index'))->assertOk();
        $this->actingAs($admin)->get(route('devoluciones.venta.create', $venta))->assertOk();
        $this->actingAs($admin)->get(route('devoluciones.compra.create', $compra))->assertOk();
    }
}
