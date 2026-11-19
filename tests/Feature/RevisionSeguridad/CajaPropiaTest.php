<?php

namespace Tests\Feature\RevisionSeguridad;

use App\Models\CajaMovimiento;
use App\Models\CajaSesion;
use App\Models\Cliente;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Tests\TestCase;

/**
 * M5: el efectivo entra y sale de la caja de quien lo opera (nunca de la de otro),
 * hay una sola caja abierta por usuario aunque dos pedidos lleguen a la vez, y el
 * arqueo es ciego: el saldo esperado no se muestra mientras la caja está abierta.
 */
class CajaPropiaTest extends TestCase
{
    private function admin(): User
    {
        return User::factory()->create(['role_id' => $this->rolAdministrador()->id]);
    }

    private function efectivo(): MetodoPago
    {
        return MetodoPago::create(['nombre' => 'Efectivo', 'codigo' => 'efectivo', 'activo' => true, 'orden' => 1]);
    }

    private function ventaEnEfectivo(User $u, Producto $p, MetodoPago $efectivo)
    {
        return $this->actingAs($u)->post(route('ventas.store'), [
            'cliente_id'   => Cliente::factory()->create()->id,
            'fecha'        => now()->toDateString(),
            'estado'       => 'completada',
            'detalles'     => [['producto_id' => $p->id, 'cantidad' => 1, 'precio' => 100]],
            'metodos_pago' => [['metodo_pago_id' => $efectivo->id, 'monto' => 121]],
        ]);
    }

    public function test_un_cobro_en_efectivo_sin_caja_propia_se_rechaza_y_no_cae_en_la_de_otro(): void
    {
        $efectivo = $this->efectivo();
        $producto = Producto::factory()->create(['stock' => 10]);
        $cajera = $this->admin();
        $vendedor = $this->admin();
        $this->actingAs($cajera)->post(route('caja.abrir'), ['monto_inicial' => 1000]);

        $this->ventaEnEfectivo($vendedor, $producto, $efectivo)->assertSessionHasErrors('metodos_pago');

        $this->assertSame(0, Venta::count(), 'La venta no debería haberse emitido.');
        $this->assertSame(10, $producto->fresh()->stock);
        $this->assertSame(0, CajaMovimiento::count());
    }

    public function test_con_caja_propia_el_efectivo_entra_en_la_propia(): void
    {
        $efectivo = $this->efectivo();
        $producto = Producto::factory()->create(['stock' => 10]);
        $usuario = $this->admin();
        $this->actingAs($usuario)->post(route('caja.abrir'), ['monto_inicial' => 1000]);

        $this->ventaEnEfectivo($usuario, $producto, $efectivo)->assertSessionHasNoErrors();

        $this->assertEquals(121, CajaSesion::first()->totalIngresos());
    }

    public function test_un_reembolso_en_efectivo_sin_caja_propia_se_rechaza(): void
    {
        $efectivo = $this->efectivo();
        $producto = Producto::factory()->create(['stock' => 10]);
        $cajera = $this->admin();
        $vendedor = $this->admin();
        $this->actingAs($cajera)->post(route('caja.abrir'), ['monto_inicial' => 1000]);
        $this->actingAs($cajera)->post(route('ventas.store'), [
            'cliente_id' => Cliente::factory()->create()->id,
            'fecha'      => now()->toDateString(),
            'estado'     => 'completada',
            'detalles'   => [['producto_id' => $producto->id, 'cantidad' => 2, 'precio' => 100]],
        ]);
        $venta = Venta::firstOrFail();

        $this->actingAs($vendedor)->post(route('devoluciones.venta.store', $venta), [
            'motivo'             => 'fallado',
            'reembolso_efectivo' => 1,
            'detalles'           => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ])->assertSessionHasErrors();

        $this->assertSame(8, $producto->fresh()->stock);
        $this->assertSame(0, CajaMovimiento::where('tipo', 'egreso')->count());
        $this->assertNotNull($efectivo);
    }

    public function test_el_indice_no_deja_dos_cajas_abiertas_para_el_mismo_usuario(): void
    {
        $usuario = $this->admin();
        CajaSesion::create(['user_id' => $usuario->id, 'monto_inicial' => 0, 'estado' => 'abierta', 'abierta_en' => now()]);

        $this->expectException(\Throwable::class);
        CajaSesion::create(['user_id' => $usuario->id, 'monto_inicial' => 0, 'estado' => 'abierta', 'abierta_en' => now()]);
    }

    public function test_dos_cajas_cerradas_o_de_usuarios_distintos_si_conviven(): void
    {
        $a = $this->admin();
        $b = $this->admin();
        CajaSesion::create(['user_id' => $a->id, 'monto_inicial' => 0, 'estado' => 'cerrada', 'abierta_en' => now()]);
        CajaSesion::create(['user_id' => $a->id, 'monto_inicial' => 0, 'estado' => 'cerrada', 'abierta_en' => now()]);
        CajaSesion::create(['user_id' => $a->id, 'monto_inicial' => 0, 'estado' => 'abierta', 'abierta_en' => now()]);
        CajaSesion::create(['user_id' => $b->id, 'monto_inicial' => 0, 'estado' => 'abierta', 'abierta_en' => now()]);

        $this->assertSame(4, CajaSesion::count());
    }

    public function test_el_arqueo_es_ciego_mientras_la_caja_esta_abierta(): void
    {
        $usuario = $this->admin();
        $this->actingAs($usuario)->post(route('caja.abrir'), ['monto_inicial' => 1234]);

        $this->actingAs($usuario)->get(route('caja.index'))->assertOk()->assertDontSee('Saldo esperado');
    }
}
