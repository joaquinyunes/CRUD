<?php

namespace Tests\Feature;

use App\Models\ComprobanteAfip;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Models\Venta;
use App\Services\FacturaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacturacionAfipTest extends TestCase
{
    use RefreshDatabase;

    private function vender(): Venta
    {
        $admin = User::factory()->create(['role_id' => Role::firstOrCreate(['nombre' => 'Administrador'])->id]);
        $efectivo = MetodoPago::firstOrCreate(['codigo' => 'efectivo'], ['nombre' => 'Efectivo', 'activo' => true, 'permite_vuelto' => true, 'orden' => 1]);
        $producto = Producto::factory()->create(['stock' => 20, 'precio_venta' => 100]);

        $this->actingAs($admin)->post(route('caja.abrir'), ['monto_inicial' => 0]);
        $this->actingAs($admin)->postJson(route('pos.store'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1, 'precio' => 100]],
            'pagos' => [['metodo_pago_id' => $efectivo->id, 'monto' => 121]],
            'recibido' => 121,
        ])->assertOk();

        return Venta::latest('id')->first();
    }

    public function test_pos_emite_comprobante_simulado_automaticamente(): void
    {
        $venta = $this->vender();

        $c = ComprobanteAfip::where('venta_id', $venta->id)->first();
        $this->assertNotNull($c);
        $this->assertSame('simulado', $c->resultado);
        $this->assertNotEmpty($c->cae);
        $this->assertEquals(121, $c->importe_total);
        $this->assertEquals(21, $c->importe_iva);
        $this->assertSame('B 0001-00000001', $c->numeroFormateado());
    }

    public function test_numero_de_comprobante_es_correlativo(): void
    {
        $this->vender();
        $this->vender();

        $numeros = ComprobanteAfip::orderBy('numero')->pluck('numero')->all();
        $this->assertSame([1, 2], $numeros);
    }

    public function test_facturar_es_idempotente(): void
    {
        $venta = $this->vender();
        $svc = app(FacturaService::class);

        $a = $svc->facturar($venta);
        $b = $svc->facturar($venta);

        $this->assertSame($a->id, $b->id);
        $this->assertSame(1, ComprobanteAfip::where('venta_id', $venta->id)->count());
    }

    public function test_setting_desactiva_facturacion_automatica(): void
    {
        Setting::establecer('afip_facturar_automatico', '0');
        $venta = $this->vender();

        $this->assertSame(0, ComprobanteAfip::where('venta_id', $venta->id)->count());
    }

    public function test_libro_iva_renderiza(): void
    {
        $this->vender();
        $admin = User::factory()->create(['role_id' => Role::firstOrCreate(['nombre' => 'Administrador'])->id]);

        $this->actingAs($admin)->get(route('reportes.libro-iva'))
            ->assertOk()
            ->assertSee('IVA débito fiscal');
    }
}
