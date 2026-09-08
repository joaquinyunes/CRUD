<?php

namespace Tests\Feature;

use App\Models\Deposito;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\ProductoLote;
use App\Models\Role;
use App\Models\User;
use App\Services\LoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoteVencimientoTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role_id' => Role::create(['nombre' => 'Administrador'])->id]);
    }

    public function test_consumo_fefo_descuenta_primero_el_lote_que_vence_antes(): void
    {
        $deposito = Deposito::create(['nombre' => 'Principal', 'es_principal' => true, 'activo' => true]);
        $producto = Producto::factory()->create(['controla_vencimiento' => true, 'stock' => 30]);

        $svc = app(LoteService::class);
        $svc->ingresar($producto->id, $deposito->id, 10, 'A', now()->addDays(5)->toDateString());
        $svc->ingresar($producto->id, $deposito->id, 20, 'B', now()->addDays(60)->toDateString());

        $consumido = $svc->consumirFEFO($producto->id, $deposito->id, 12);

        $this->assertEquals(12, $consumido);
        $this->assertEquals(0, ProductoLote::where('lote', 'A')->value('cantidad'));
        $this->assertEquals(18, ProductoLote::where('lote', 'B')->value('cantidad'));
    }

    public function test_venta_pos_consume_lotes_fefo(): void
    {
        $admin = $this->admin();
        Deposito::create(['nombre' => 'Principal', 'es_principal' => true, 'activo' => true]);
        $efectivo = MetodoPago::create(['nombre' => 'Efectivo', 'codigo' => 'efectivo', 'activo' => true, 'permite_vuelto' => true, 'orden' => 1]);
        $producto = Producto::factory()->create(['controla_vencimiento' => true, 'stock' => 20, 'precio_venta' => 100]);
        $dep = Deposito::principalId();

        app(LoteService::class)->ingresar($producto->id, $dep, 20, 'L1', now()->addDays(3)->toDateString());

        $this->actingAs($admin)->post(route('caja.abrir'), ['monto_inicial' => 0]);
        $this->actingAs($admin)->postJson(route('pos.store'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 5, 'precio' => 100]],
            'pagos' => [['metodo_pago_id' => $efectivo->id, 'monto' => 605]],
            'recibido' => 605,
        ])->assertOk();

        $this->assertEquals(15, ProductoLote::where('lote', 'L1')->value('cantidad'));
    }

    public function test_comando_alerta_genera_notificacion(): void
    {
        $this->admin();
        $deposito = Deposito::create(['nombre' => 'Principal', 'es_principal' => true, 'activo' => true]);
        $producto = Producto::factory()->create(['controla_vencimiento' => true, 'dias_alerta_vencimiento' => 30]);
        ProductoLote::create([
            'producto_id' => $producto->id, 'deposito_id' => $deposito->id,
            'lote' => 'X', 'vencimiento' => now()->addDays(10)->toDateString(), 'cantidad' => 5,
        ]);

        $this->artisan('lotes:alertar-vencimientos')->assertSuccessful();
        $this->assertDatabaseHas('notificaciones', ['tipo' => 'stock', 'url' => '/lotes']);
    }

    public function test_pantalla_lotes_carga(): void
    {
        Deposito::create(['nombre' => 'Principal', 'es_principal' => true, 'activo' => true]);
        $this->actingAs($this->admin())->get(route('lotes.index'))->assertOk();
    }
}
