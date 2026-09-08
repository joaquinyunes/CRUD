<?php

namespace Tests\Feature;

use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\Role;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContingenciaOfflineTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role_id' => Role::firstOrCreate(['nombre' => 'Administrador'])->id]);
    }

    public function test_catalogo_para_busqueda_offline(): void
    {
        $admin = $this->admin();
        Producto::factory()->create(['nombre' => 'Café molido', 'codigo_barra' => '779000111222']);

        $this->actingAs($admin)->getJson(route('pos.catalogo'))
            ->assertOk()
            ->assertJsonStructure(['generado', 'productos' => [['id', 'nombre', 'precio', 'codigos']]])
            ->assertJsonFragment(['nombre' => 'Café molido']);
    }

    public function test_reenvio_con_misma_idempotencia_no_duplica_la_venta(): void
    {
        $admin = $this->admin();
        $efectivo = MetodoPago::firstOrCreate(['codigo' => 'efectivo'], ['nombre' => 'Efectivo', 'activo' => true, 'permite_vuelto' => true, 'orden' => 1]);
        $producto = Producto::factory()->create(['stock' => 20, 'precio_venta' => 100]);
        $this->actingAs($admin)->post(route('caja.abrir'), ['monto_inicial' => 0]);

        $payload = [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 2, 'precio' => 100]],
            'pagos' => [['metodo_pago_id' => $efectivo->id, 'monto' => 242]],
            'recibido' => 242,
            'idempotencia' => 'pos-abc-123',
        ];

        $r1 = $this->actingAs($admin)->postJson(route('pos.store'), $payload)->assertOk();
        $r2 = $this->actingAs($admin)->postJson(route('pos.store'), $payload)->assertOk();

        $this->assertSame($r1->json('numero'), $r2->json('numero'));
        $this->assertSame(1, Venta::where('idempotencia', 'pos-abc-123')->count());
        $this->assertSame(18, $producto->fresh()->stock); // se descontó una sola vez
    }

    public function test_service_worker_se_sirve(): void
    {
        $this->get('/sw.js')->assertOk();
    }
}
