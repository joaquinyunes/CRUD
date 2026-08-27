<?php

namespace Tests\Feature;

use App\Models\CajaSesion;
use App\Models\Cliente;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CajaTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role_id' => Role::create(['nombre' => 'Administrador'])->id]);
    }

    public function test_flujo_de_caja_con_venta_en_efectivo_y_arqueo(): void
    {
        $admin = $this->admin();
        $efectivo = MetodoPago::create(['nombre' => 'Efectivo', 'codigo' => 'efectivo', 'activo' => true, 'orden' => 1]);
        $cliente = Cliente::factory()->create();
        $producto = Producto::factory()->create(['stock' => 100]);

        $this->actingAs($admin)->post(route('caja.abrir'), ['monto_inicial' => 1000])
            ->assertRedirect(route('caja.index'));

        $sesion = CajaSesion::first();
        $this->assertSame('abierta', $sesion->estado);

        // Venta pagada en efectivo: 1 x 100 + 21% = 121
        $this->actingAs($admin)->post(route('ventas.store'), [
            'cliente_id'   => $cliente->id,
            'fecha'        => now()->toDateString(),
            'estado'       => 'completada',
            'detalles'     => [['producto_id' => $producto->id, 'cantidad' => 1, 'precio' => 100]],
            'metodos_pago' => [['metodo_pago_id' => $efectivo->id, 'monto' => 121]],
        ]);

        $this->assertEquals(121, $sesion->fresh()->totalIngresos());

        // Egreso manual
        $this->actingAs($admin)->post(route('caja.movimiento'), [
            'tipo' => 'egreso', 'concepto' => 'Compra insumos', 'monto' => 50,
        ]);

        // esperado = 1000 + 121 - 50 = 1071
        $this->assertEquals(1071, $sesion->fresh()->saldoEsperado());

        $this->actingAs($admin)->post(route('caja.cerrar'), ['monto_final_declarado' => 1060]);

        $sesion->refresh();
        $this->assertSame('cerrada', $sesion->estado);
        $this->assertEquals(1071, $sesion->monto_final_sistema);
        $this->assertEquals(-11, $sesion->diferencia);
    }

    public function test_no_se_puede_abrir_dos_cajas(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('caja.abrir'), ['monto_inicial' => 0]);
        $this->actingAs($admin)->post(route('caja.abrir'), ['monto_inicial' => 0])
            ->assertSessionHasErrors('monto_inicial');

        $this->assertDatabaseCount('caja_sesiones', 1);
    }
}
