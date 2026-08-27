<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\Role;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CuentaCorrienteTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role_id' => Role::create(['nombre' => 'Administrador'])->id]);
    }

    private function ventaPayload(Producto $p, Cliente $c, array $o = []): array
    {
        return array_merge([
            'cliente_id' => $c->id,
            'fecha'      => now()->toDateString(),
            'estado'     => 'completada',
            'detalles'   => [['producto_id' => $p->id, 'cantidad' => 10, 'precio' => 100]],
        ], $o);
    }

    public function test_limite_de_credito_bloquea_la_venta(): void
    {
        $cliente = Cliente::factory()->create(['limite_credito' => 500]);
        $producto = Producto::factory()->create(['stock' => 100]);

        $this->actingAs($this->admin())
            ->post(route('ventas.store'), $this->ventaPayload($producto, $cliente))
            ->assertSessionHasErrors('cliente_id');

        $this->assertDatabaseCount('ventas', 0);
    }

    public function test_venta_a_credito_genera_saldo_y_cobro_fifo_lo_reduce(): void
    {
        $cliente = Cliente::factory()->create(['limite_credito' => 100000]);
        $producto = Producto::factory()->create(['stock' => 100]);
        $metodo = MetodoPago::create(['nombre' => 'Transferencia', 'codigo' => 'transferencia', 'activo' => true, 'orden' => 1]);
        $admin = $this->admin();

        // 10 x 100 = 1000 + 21% IVA = 1210, sin pago
        $this->actingAs($admin)->post(route('ventas.store'), $this->ventaPayload($producto, $cliente));
        $venta = Venta::first();
        $this->assertEquals(1210, $venta->total_final);
        $this->assertEquals(1210, $cliente->fresh()->saldo());

        $this->actingAs($admin)->post(route('cuentas.cliente.cobrar', $cliente), [
            'metodo_pago_id' => $metodo->id,
            'monto'          => 700,
        ])->assertSessionHasNoErrors();

        $this->assertEquals(510, $cliente->fresh()->saldo());
        $this->assertSame('parcial', $venta->fresh()->estado_pago);
    }

    public function test_no_se_puede_cobrar_mas_que_la_deuda(): void
    {
        $cliente = Cliente::factory()->create(['limite_credito' => 100000]);
        $producto = Producto::factory()->create(['stock' => 100]);
        $metodo = MetodoPago::create(['nombre' => 'Transferencia', 'codigo' => 'transferencia', 'activo' => true, 'orden' => 1]);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('ventas.store'), $this->ventaPayload($producto, $cliente));

        $this->actingAs($admin)->post(route('cuentas.cliente.cobrar', $cliente), [
            'metodo_pago_id' => $metodo->id,
            'monto'          => 5000,
        ])->assertSessionHasErrors('monto');

        $this->assertEquals(1210, $cliente->fresh()->saldo());
    }
}
