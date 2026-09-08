<?php

namespace Tests\Feature;

use App\Models\ListaPrecio;
use App\Models\MetodoPago;
use App\Models\PrecioProducto;
use App\Models\Producto;
use App\Models\Promocion;
use App\Models\Role;
use App\Models\User;
use App\Models\Venta;
use App\Services\PrecioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromocionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role_id' => Role::create(['nombre' => 'Administrador'])->id]);
    }

    private function abrirCaja(User $u): void
    {
        MetodoPago::firstOrCreate(['codigo' => 'efectivo'], ['nombre' => 'Efectivo', 'activo' => true, 'permite_vuelto' => true, 'orden' => 1]);
        $this->actingAs($u)->post(route('caja.abrir'), ['monto_inicial' => 1000]);
    }

    public function test_descuento_porcentaje_por_producto(): void
    {
        $p = Producto::factory()->create(['precio_venta' => 100]);
        $promo = Promocion::create([
            'nombre' => '10% off', 'tipo' => 'porcentaje', 'valor' => 10,
            'alcance' => 'producto', 'producto_id' => $p->id, 'activa' => true,
        ]);

        $best = app(PrecioService::class)->mejorPromocion($p->fresh(), 3, 100);
        $this->assertEquals(30, $best['descuento']); // 3 * 100 * 10%
        $this->assertEquals($promo->id, $best['promocion']->id);
    }

    public function test_promo_nxm(): void
    {
        $p = Producto::factory()->create(['precio_venta' => 50]);
        Promocion::create(['nombre' => '3x2', 'tipo' => 'nxm', 'n' => 3, 'm' => 2, 'alcance' => 'producto', 'producto_id' => $p->id, 'activa' => true]);

        // 7 unidades => 2 grupos completos => 2 gratis => 100 de descuento
        $best = app(PrecioService::class)->mejorPromocion($p->fresh(), 7, 50);
        $this->assertEquals(100, $best['descuento']);
    }

    public function test_promo_fuera_de_franja_horaria_no_aplica(): void
    {
        $p = Producto::factory()->create(['precio_venta' => 100]);
        Promocion::create([
            'nombre' => 'happy hour', 'tipo' => 'porcentaje', 'valor' => 50,
            'alcance' => 'producto', 'producto_id' => $p->id, 'activa' => true,
            'hora_desde' => '18:00', 'hora_hasta' => '20:00',
        ]);

        $mediodia = now()->setTime(12, 0);
        $this->assertNull(app(PrecioService::class)->mejorPromocion($p->fresh(), 1, 100, $mediodia));

        $tarde = now()->setTime(19, 0);
        $this->assertNotNull(app(PrecioService::class)->mejorPromocion($p->fresh(), 1, 100, $tarde));
    }

    public function test_lista_de_precios_por_cliente(): void
    {
        $lista = ListaPrecio::create(['nombre' => 'Mayorista', 'ajuste_pct' => -20, 'activa' => true]);
        $p = Producto::factory()->create(['precio_venta' => 100]);

        $this->assertEquals(80, app(PrecioService::class)->precioBase($p, $lista->id));

        PrecioProducto::create(['producto_id' => $p->id, 'lista_precio_id' => $lista->id, 'precio' => 75]);
        $this->assertEquals(75, app(PrecioService::class)->precioBase($p->fresh(), $lista->id));
    }

    public function test_pos_aplica_promocion_al_vender(): void
    {
        $admin = $this->admin();
        $this->abrirCaja($admin);
        $efectivo = MetodoPago::where('codigo', 'efectivo')->first();
        $p = Producto::factory()->create(['precio_venta' => 100, 'stock' => 20]);
        Promocion::create(['nombre' => '2x1', 'tipo' => 'nxm', 'n' => 2, 'm' => 1, 'alcance' => 'producto', 'producto_id' => $p->id, 'activa' => true]);

        // 2 unidades: 1 gratis => base 200, promo -100 => neto 100 + 21% = 121
        $this->actingAs($admin)->postJson(route('pos.store'), [
            'items' => [['producto_id' => $p->id, 'cantidad' => 2, 'precio' => 100]],
            'pagos' => [['metodo_pago_id' => $efectivo->id, 'monto' => 121]],
            'recibido' => 121,
        ])->assertOk();

        $venta = Venta::with('detalles')->first();
        $this->assertEquals(121, $venta->total_final);
        $this->assertEquals(100, $venta->detalles->first()->descuento_promo);
    }

    public function test_precio_manual_ignora_promocion(): void
    {
        $admin = $this->admin();
        $this->abrirCaja($admin);
        $efectivo = MetodoPago::where('codigo', 'efectivo')->first();
        $p = Producto::factory()->create(['precio_venta' => 100, 'stock' => 20]);
        Promocion::create(['nombre' => '50%', 'tipo' => 'porcentaje', 'valor' => 50, 'alcance' => 'producto', 'producto_id' => $p->id, 'activa' => true]);

        $this->actingAs($admin)->postJson(route('pos.store'), [
            'items' => [['producto_id' => $p->id, 'cantidad' => 1, 'precio' => 90, 'precio_manual' => true]],
            'pagos' => [['metodo_pago_id' => $efectivo->id, 'monto' => 108.9]],
            'recibido' => 108.9,
        ])->assertOk();

        $venta = Venta::with('detalles')->first();
        $this->assertEquals(0, $venta->detalles->first()->descuento_promo);
        $this->assertEquals(90, $venta->detalles->first()->precio);
    }

    public function test_pantallas_promociones_cargan(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('promociones.index'))->assertOk();
        $this->actingAs($admin)->get(route('promociones.create'))->assertOk();
        $this->actingAs($admin)->get(route('listas-precio.index'))->assertOk();
    }
}
