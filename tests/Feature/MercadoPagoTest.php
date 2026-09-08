<?php

namespace Tests\Feature;

use App\Models\MetodoPago;
use App\Models\PagoPasarela;
use App\Models\Producto;
use App\Models\Role;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MercadoPagoTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role_id' => Role::firstOrCreate(['nombre' => 'Administrador'])->id]);
    }

    public function test_cobro_qr_simulado_se_aprueba(): void
    {
        $admin = $this->admin();

        $resp = $this->actingAs($admin)->postJson(route('pos.cobro-qr.crear'), [
            'monto' => 500, 'referencia' => 'POS-1',
        ]);

        $resp->assertOk()->assertJsonPath('estado', 'aprobado');
        $this->assertDatabaseHas('pagos_pasarela', ['external_ref' => 'POS-1', 'monto' => 500, 'estado' => 'aprobado']);
    }

    public function test_venta_pos_vincula_el_cobro_qr(): void
    {
        $admin = $this->admin();
        $efectivo = MetodoPago::firstOrCreate(['codigo' => 'efectivo'], ['nombre' => 'Efectivo', 'activo' => true, 'permite_vuelto' => true, 'orden' => 1]);
        $qr = MetodoPago::create(['nombre' => 'QR', 'codigo' => 'qr', 'activo' => true, 'orden' => 5]);
        $producto = Producto::factory()->create(['stock' => 10, 'precio_venta' => 100]);

        $this->actingAs($admin)->postJson(route('pos.cobro-qr.crear'), ['monto' => 121, 'referencia' => 'POS-XYZ']);

        $this->actingAs($admin)->post(route('caja.abrir'), ['monto_inicial' => 0]);
        $this->actingAs($admin)->postJson(route('pos.store'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1, 'precio' => 100]],
            'pagos' => [['metodo_pago_id' => $qr->id, 'monto' => 121]],
            'qr_ref' => 'POS-XYZ',
        ])->assertOk();

        $venta = Venta::latest('id')->first();
        $this->assertEquals($venta->id, PagoPasarela::where('external_ref', 'POS-XYZ')->value('venta_id'));
    }

    public function test_webhook_no_rompe_con_payload_vacio(): void
    {
        $this->postJson(route('webhooks.mercadopago'), [])->assertOk();
    }

    public function test_conciliacion_renderiza(): void
    {
        PagoPasarela::create(['pasarela' => 'mercadopago', 'external_ref' => 'R1', 'monto' => 200, 'estado' => 'aprobado', 'neto_acreditado' => 195, 'comision' => 5]);

        $this->actingAs($this->admin())->get(route('reportes.conciliacion-mp'))
            ->assertOk()->assertSee('Neto a acreditar');
    }
}
