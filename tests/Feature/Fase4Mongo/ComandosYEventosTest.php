<?php

namespace Tests\Feature\Fase4Mongo;

use App\Events\StockBajo;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Notificacion;
use App\Models\Presupuesto;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Fase 4: los comandos programados y los eventos sobre compras y presupuestos en MongoDB.
 */
class ComandosYEventosTest extends TestCase
{
    public function test_presupuestos_vencer_marca_solo_los_que_pasaron_su_validez(): void
    {
        $cliente = Cliente::factory()->create();
        $base = ['cliente_id' => $cliente->id, 'subtotal' => 0, 'total' => 0, 'estado' => 'enviado', 'validez_dias' => 10];

        $viejo = Presupuesto::create($base + ['numero' => 'PRE-00001', 'fecha' => now()->subDays(30)]);
        $vigente = Presupuesto::create($base + ['numero' => 'PRE-00002', 'fecha' => now()->subDays(2)]);
        $aceptado = Presupuesto::create(['estado' => 'aceptado'] + $base + ['numero' => 'PRE-00003', 'fecha' => now()->subDays(60)]);

        $this->artisan('presupuestos:vencer')->expectsOutput('Presupuestos vencidos: 1.')->assertSuccessful();

        $this->assertSame('vencido', $viejo->fresh()->estado);
        $this->assertSame('enviado', $vigente->fresh()->estado);
        $this->assertSame('aceptado', $aceptado->fresh()->estado);
    }

    public function test_avisar_stock_critico_dispara_el_evento_por_cada_producto_en_el_minimo(): void
    {
        Event::fake([StockBajo::class]);
        $critico = Producto::factory()->create(['stock' => 2, 'stock_minimo' => 5]);
        $justo = Producto::factory()->create(['stock' => 5, 'stock_minimo' => 5]);
        Producto::factory()->create(['stock' => 50, 'stock_minimo' => 5]);
        Producto::factory()->create(['stock' => 0, 'stock_minimo' => 5, 'estado' => 'inactivo']);

        $this->artisan('stock:avisar-critico')->expectsOutput('Productos en stock crítico: 2.')->assertSuccessful();

        Event::assertDispatched(StockBajo::class, 2);
        Event::assertDispatched(StockBajo::class, fn ($e) => $e->producto->id === $critico->id);
        Event::assertDispatched(StockBajo::class, fn ($e) => $e->producto->id === $justo->id);
    }

    public function test_el_aviso_de_stock_bajo_llega_a_los_administradores(): void
    {
        $admin = User::factory()->create(['role_id' => $this->rolAdministrador()->id]);
        User::factory()->create(); // sin rol: no recibe nada
        Producto::factory()->create(['stock' => 1, 'stock_minimo' => 5, 'nombre' => 'Tornillo']);

        $this->artisan('stock:avisar-critico')->assertSuccessful();

        $avisos = Notificacion::where('tipo', 'stock')->get();
        $this->assertCount(1, $avisos);
        $this->assertSame($admin->id, (int) $avisos->first()->user_id);
        $this->assertStringContainsString('Tornillo', $avisos->first()->mensaje);
    }

    public function test_las_compras_guardan_el_proveedor_y_se_buscan_por_su_nombre(): void
    {
        $proveedor = Proveedor::factory()->create(['nombre' => 'Ferretería Sur']);
        $otro = Proveedor::factory()->create(['nombre' => 'Mayorista Norte']);
        $base = ['fecha' => now(), 'total' => 1, 'estado' => 'pendiente'];
        $suya = Compra::create($base + ['numero' => 'COM-00001', 'proveedor_id' => $proveedor->id]);
        Compra::create($base + ['numero' => 'COM-00002', 'proveedor_id' => $otro->id]);

        $this->assertSame([$suya->id], Compra::buscar('Sur')->get()->modelKeys());
        $this->assertSame($proveedor->id, $suya->fresh()->proveedor->id);
    }
}
