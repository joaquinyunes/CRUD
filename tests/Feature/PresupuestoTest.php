<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Presupuesto;
use App\Models\Producto;
use App\Models\Role;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PresupuestoTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role_id' => Role::create(['nombre' => 'Administrador'])->id]);
    }

    public function test_crear_presupuesto_no_toca_stock_y_calcula_total(): void
    {
        $admin = $this->admin();
        $cliente = Cliente::factory()->create();
        $producto = Producto::factory()->create(['stock' => 30]);

        $this->actingAs($admin)->post(route('presupuestos.store'), [
            'cliente_id' => $cliente->id,
            'fecha'      => now()->toDateString(),
            'detalles'   => [['producto_id' => $producto->id, 'cantidad' => 4, 'precio' => 100]],
        ])->assertRedirect(route('presupuestos.index'));

        $pre = Presupuesto::first();
        $this->assertEquals(400, $pre->subtotal);
        $this->assertEquals(484, $pre->total); // + 21%
        $this->assertSame(30, $producto->fresh()->stock);
    }

    public function test_convertir_genera_venta_pendiente_vinculada(): void
    {
        $admin = $this->admin();
        $cliente = Cliente::factory()->create();
        $producto = Producto::factory()->create(['stock' => 30]);

        $this->actingAs($admin)->post(route('presupuestos.store'), [
            'cliente_id' => $cliente->id,
            'fecha'      => now()->toDateString(),
            'detalles'   => [['producto_id' => $producto->id, 'cantidad' => 4, 'precio' => 100]],
        ]);
        $pre = Presupuesto::first();

        $this->actingAs($admin)->post(route('presupuestos.convertir', $pre))
            ->assertRedirect(route('ventas.edit', Venta::first()));

        $pre->refresh();
        $venta = Venta::first();
        $this->assertSame('convertido', $pre->estado);
        $this->assertSame($venta->id, $pre->venta_id);
        $this->assertSame('pendiente', $venta->estado);
        $this->assertSame(30, $producto->fresh()->stock); // sigue sin descontar hasta completar
        $this->assertEquals(484, $venta->total_final);
    }

    public function test_no_se_convierte_dos_veces(): void
    {
        $admin = $this->admin();
        $cliente = Cliente::factory()->create();
        $producto = Producto::factory()->create(['stock' => 30]);

        $this->actingAs($admin)->post(route('presupuestos.store'), [
            'cliente_id' => $cliente->id, 'fecha' => now()->toDateString(),
            'detalles'   => [['producto_id' => $producto->id, 'cantidad' => 1, 'precio' => 100]],
        ]);
        $pre = Presupuesto::first();
        $this->actingAs($admin)->post(route('presupuestos.convertir', $pre));
        $this->actingAs($admin)->post(route('presupuestos.convertir', $pre))->assertSessionHasErrors('general');

        $this->assertSame(1, Venta::count());
    }
}
