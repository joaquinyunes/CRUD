<?php

namespace Tests\Feature\RevisionSeguridad;

use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Devolucion;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use App\Models\Venta;
use Tests\TestCase;

/**
 * H1 y H2 de la revisión de seguridad: devolver o anular sin que el documento
 * haya movido stock, o anular uno ya devuelto, fabrica stock que no existe.
 */
class DevolucionesYAnulacionesTest extends TestCase
{
    private function admin(): User
    {
        return User::factory()->create(['role_id' => $this->rolAdministrador()->id]);
    }

    private function vender(User $admin, Producto $p, string $estado = 'completada', int $cantidad = 10): Venta
    {
        $this->actingAs($admin)->post(route('ventas.store'), [
            'cliente_id' => Cliente::factory()->create(['limite_credito' => 1000000])->id,
            'fecha'      => now()->toDateString(),
            'estado'     => $estado,
            'detalles'   => [['producto_id' => $p->id, 'cantidad' => $cantidad, 'precio' => 100]],
        ])->assertSessionHasNoErrors();

        return Venta::latest('id')->firstOrFail();
    }

    private function comprar(User $admin, Producto $p, string $estado = 'completada'): Compra
    {
        $this->actingAs($admin)->post(route('compras.store'), [
            'proveedor_id' => Proveedor::factory()->create()->id,
            'fecha'        => now()->toDateString(),
            'estado'       => $estado,
            'detalles'     => [['producto_id' => $p->id, 'cantidad' => 10, 'precio' => 50]],
        ])->assertSessionHasNoErrors();

        return Compra::latest('id')->firstOrFail();
    }

    // ---- H1 -----------------------------------------------------------------

    public function test_no_se_devuelve_una_venta_que_nunca_movio_stock(): void
    {
        $admin = $this->admin();
        $producto = Producto::factory()->create(['stock' => 20]);
        $pendiente = $this->vender($admin, $producto, 'pendiente');
        $this->assertSame(20, $producto->fresh()->stock);

        $this->actingAs($admin)->get(route('devoluciones.venta.create', $pendiente))->assertNotFound();
        $this->actingAs($admin)->post(route('devoluciones.venta.store', $pendiente), [
            'motivo'   => 'fallado',
            'detalles' => [['producto_id' => $producto->id, 'cantidad' => 4]],
        ])->assertNotFound();

        $this->assertSame(20, $producto->fresh()->stock, 'Una devolución sobre una venta sin stock aplicado fabrica stock.');
        $this->assertSame(0, Devolucion::count());
    }

    public function test_no_se_devuelve_una_compra_que_nunca_movio_stock(): void
    {
        $admin = $this->admin();
        $producto = Producto::factory()->create(['stock' => 20]);
        $pendiente = $this->comprar($admin, $producto, 'pendiente');

        $this->actingAs($admin)->get(route('devoluciones.compra.create', $pendiente))->assertNotFound();
        $this->actingAs($admin)->post(route('devoluciones.compra.store', $pendiente), [
            'motivo'   => 'fallado',
            'detalles' => [['producto_id' => $producto->id, 'cantidad' => 4]],
        ])->assertNotFound();

        $this->assertSame(20, $producto->fresh()->stock);
        $this->assertSame(0, Devolucion::count());
    }

    // ---- H2 -----------------------------------------------------------------

    public function test_no_se_anula_una_venta_ya_devuelta_porque_duplicaria_el_stock(): void
    {
        $admin = $this->admin();
        $producto = Producto::factory()->create(['stock' => 20]);
        $venta = $this->vender($admin, $producto);                       // 20 - 10 = 10
        $this->actingAs($admin)->post(route('devoluciones.venta.store', $venta), [
            'motivo'   => 'fallado',
            'detalles' => [['producto_id' => $producto->id, 'cantidad' => 4]],
        ]);                                                              // 10 + 4 = 14
        $this->assertSame(14, $producto->fresh()->stock);

        $this->actingAs($admin)->delete(route('ventas.destroy', $venta))->assertSessionHasErrors('general');

        $this->assertSame(14, $producto->fresh()->stock, 'Anular una venta devuelta no puede devolver el stock otra vez.');
        $this->assertNotSame('anulada', $venta->fresh()->estado);
    }

    public function test_no_se_edita_una_venta_ya_devuelta(): void
    {
        $admin = $this->admin();
        $producto = Producto::factory()->create(['stock' => 20]);
        $venta = $this->vender($admin, $producto);
        $this->actingAs($admin)->post(route('devoluciones.venta.store', $venta), [
            'motivo'   => 'fallado',
            'detalles' => [['producto_id' => $producto->id, 'cantidad' => 4]],
        ]);

        $this->actingAs($admin)->put(route('ventas.update', $venta), [
            'cliente_id' => $venta->cliente_id,
            'fecha'      => now()->toDateString(),
            'estado'     => 'completada',
            'detalles'   => [['producto_id' => $producto->id, 'cantidad' => 1, 'precio' => 100]],
        ])->assertSessionHasErrors('general');

        $this->assertSame(14, $producto->fresh()->stock);
        $this->assertSame(10, $venta->fresh()->detalles()->first()->cantidad);
    }

    public function test_la_api_tampoco_anula_una_venta_devuelta(): void
    {
        $admin = $this->admin();
        $producto = Producto::factory()->create(['stock' => 20]);
        $venta = $this->vender($admin, $producto);
        $this->actingAs($admin)->post(route('devoluciones.venta.store', $venta), [
            'motivo'   => 'fallado',
            'detalles' => [['producto_id' => $producto->id, 'cantidad' => 4]],
        ]);

        $this->actingAs($admin, 'sanctum')->deleteJson("/api/ventas/{$venta->id}")->assertStatus(422);
        $this->assertSame(14, $producto->fresh()->stock);
    }

    public function test_no_se_anula_una_compra_con_devoluciones_a_proveedor(): void
    {
        $admin = $this->admin();
        $producto = Producto::factory()->create(['stock' => 0]);
        $compra = $this->comprar($admin, $producto);                     // 0 + 10 = 10
        $this->actingAs($admin)->post(route('devoluciones.compra.store', $compra), [
            'motivo'   => 'fallado',
            'detalles' => [['producto_id' => $producto->id, 'cantidad' => 4]],
        ]);                                                              // 10 - 4 = 6
        $this->assertSame(6, $producto->fresh()->stock);

        $this->actingAs($admin)->delete(route('compras.destroy', $compra))->assertSessionHasErrors('general');
        $this->actingAs($admin, 'sanctum')->deleteJson("/api/compras/{$compra->id}")->assertStatus(422);

        $this->assertSame(6, $producto->fresh()->stock);
    }

    // ---- H3 -----------------------------------------------------------------

    public function test_devoluciones_simultaneas_no_devuelven_mas_de_lo_vendido(): void
    {
        $admin = $this->admin();
        $producto = Producto::factory()->create(['stock' => 20]);
        $venta = $this->vender($admin, $producto, 'completada', 3);     // 20 - 3 = 17
        $this->assertSame(17, $producto->fresh()->stock);

        $largada = microtime(true) + 6.0;
        $hijos = [];
        for ($i = 0; $i < 6; $i++) {
            $proceso = proc_open(
                [PHP_BINARY, base_path('tests/Support/devolver_worker.php'), (string) $venta->id, (string) $producto->id, '1', (string) $largada, '2'],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes,
                base_path(),
                array_merge(getenv(), [
                    'APP_ENV'          => 'testing',
                    'MONGODB_DATABASE' => config('database.connections.mongodb.database'),
                    'MONGODB_URI'      => config('database.connections.mongodb.dsn'),
                    'QUEUE_CONNECTION' => 'sync',
                    'CACHE_STORE'      => 'array',
                    'SESSION_DRIVER'   => 'array',
                ])
            );
            $hijos[] = [$proceso, $pipes];
        }

        $respuestas = [];
        foreach ($hijos as $h) {
            [$proceso, $pipes] = $h;
            $salida = trim(stream_get_contents($pipes[1]));
            proc_close($proceso);
            $respuestas = array_merge($respuestas, json_decode($salida, true) ?? [['ok' => false, 'error' => "sin respuesta: {$salida}"]]);
        }

        $this->assertCount(3, array_filter($respuestas, fn ($r) => $r['ok']), json_encode($respuestas));
        $this->assertCount(9, array_filter($respuestas, fn ($r) => ! $r['ok'] && $r['error'] === 'regla'), json_encode($respuestas));
        $this->assertSame(3, (int) Devolucion::count());
        $this->assertSame(20, $producto->fresh()->stock, 'Se devolvió más de lo vendido: sobra stock.');
    }
}
