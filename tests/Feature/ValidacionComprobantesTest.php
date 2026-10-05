<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ventas y compras comparten un único juego de reglas de validación, así que lo
 * que vale para una tiene que valer para la otra.
 */
class ValidacionComprobantesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(PermissionSeeder::class);

        return User::factory()->create(['role_id' => $this->rolAdministrador()->id]);
    }

    /** @return array<string,mixed> */
    private function venta(array $cambios = []): array
    {
        return array_merge([
            'cliente_id' => Cliente::factory()->create()->id,
            'fecha'      => now()->toDateString(),
            'estado'     => 'completada',
            'detalles'   => [['producto_id' => Producto::factory()->create(['stock' => 50])->id, 'cantidad' => 2, 'precio' => 100]],
        ], $cambios);
    }

    public function test_una_venta_sin_lineas_se_rechaza(): void
    {
        $this->actingAs($this->admin())
            ->post(route('ventas.store'), $this->venta(['detalles' => []]))
            ->assertSessionHasErrors('detalles');

        $this->assertDatabaseCount('ventas', 0);
    }

    public function test_no_se_vende_cantidad_cero(): void
    {
        $venta = $this->venta();
        $venta['detalles'][0]['cantidad'] = 0;

        $this->actingAs($this->admin())
            ->post(route('ventas.store'), $venta)
            ->assertSessionHasErrors('detalles.0.cantidad');
    }

    public function test_no_se_vende_a_precio_negativo(): void
    {
        $venta = $this->venta();
        $venta['detalles'][0]['precio'] = -10;

        $this->actingAs($this->admin())
            ->post(route('ventas.store'), $venta)
            ->assertSessionHasErrors('detalles.0.precio');
    }

    public function test_un_descuento_mayor_al_cien_por_ciento_se_rechaza(): void
    {
        $this->actingAs($this->admin())
            ->post(route('ventas.store'), $this->venta([
                'descuento_tipo' => 'porcentaje',
                'descuento'      => 150,
            ]))
            ->assertSessionHasErrors('descuento');

        $this->assertDatabaseCount('ventas', 0);
    }

    public function test_las_compras_aplican_las_mismas_reglas_que_las_ventas(): void
    {
        $admin = $this->admin();

        $compra = [
            'proveedor_id' => Proveedor::factory()->create()->id,
            'fecha'        => now()->toDateString(),
            'estado'       => 'completada',
            'detalles'     => [['producto_id' => Producto::factory()->create()->id, 'cantidad' => 0, 'precio' => 50]],
        ];

        $this->actingAs($admin)->post(route('compras.store'), $compra)
            ->assertSessionHasErrors('detalles.0.cantidad');

        $compra['detalles'][0]['cantidad'] = 1;
        $compra['descuento_tipo'] = 'porcentaje';
        $compra['descuento'] = 150;

        $this->actingAs($admin)->post(route('compras.store'), $compra)
            ->assertSessionHasErrors('descuento');
    }

    public function test_una_venta_valida_pasa(): void
    {
        $this->actingAs($this->admin())
            ->post(route('ventas.store'), $this->venta())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('ventas', 1);
    }
}
