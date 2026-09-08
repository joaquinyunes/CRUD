<?php

namespace Tests\Feature;

use App\Models\Deposito;
use App\Models\Producto;
use App\Models\Recuento;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventarioTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role_id' => Role::create(['nombre' => 'Administrador'])->id]);
    }

    public function test_merma_descuenta_stock_y_registra_costo(): void
    {
        $admin = $this->admin();
        Deposito::create(['nombre' => 'Principal', 'es_principal' => true, 'activo' => true]);
        $producto = Producto::factory()->create(['stock' => 20, 'precio_compra' => 50]);

        $this->actingAs($admin)->post(route('mermas.store'), [
            'producto_id' => $producto->id,
            'deposito_id' => Deposito::principalId(),
            'cantidad' => 3,
            'motivo' => 'rotura',
        ])->assertSessionHasNoErrors();

        $this->assertSame(17, $producto->fresh()->stock);
        $this->assertDatabaseHas('mermas', ['producto_id' => $producto->id, 'cantidad' => 3, 'motivo' => 'rotura', 'costo' => 150]);
        $this->assertDatabaseHas('movimientos_stock', ['producto_id' => $producto->id, 'tipo' => 'salida']);
    }

    public function test_recuento_ajusta_stock_a_lo_contado(): void
    {
        $admin = $this->admin();
        Deposito::create(['nombre' => 'Principal', 'es_principal' => true, 'activo' => true]);
        $p1 = Producto::factory()->create(['stock' => 10]);
        $p2 = Producto::factory()->create(['stock' => 5]);

        $this->actingAs($admin)->post(route('recuentos.store'), ['deposito_id' => Deposito::principalId()])
            ->assertRedirect();

        $recuento = Recuento::first();
        $this->assertSame(2, $recuento->detalles()->count());

        $this->actingAs($admin)->put(route('recuentos.guardar', $recuento), [
            'contado' => [$p1->id => 8, $p2->id => 5],
        ])->assertSessionHasNoErrors();

        $this->actingAs($admin)->post(route('recuentos.aplicar', $recuento))->assertRedirect();

        $this->assertSame('aplicado', $recuento->fresh()->estado);
        $this->assertSame(8, $p1->fresh()->stock);   // ajustado
        $this->assertSame(5, $p2->fresh()->stock);   // sin cambio
        $this->assertDatabaseHas('movimientos_stock', ['producto_id' => $p1->id, 'tipo' => 'ajuste']);
    }

    public function test_no_se_aplica_dos_veces(): void
    {
        $admin = $this->admin();
        Deposito::create(['nombre' => 'Principal', 'es_principal' => true, 'activo' => true]);
        Producto::factory()->create(['stock' => 10]);
        $this->actingAs($admin)->post(route('recuentos.store'), ['deposito_id' => Deposito::principalId()]);
        $recuento = Recuento::first();

        $this->actingAs($admin)->post(route('recuentos.aplicar', $recuento));
        $this->actingAs($admin)->post(route('recuentos.aplicar', $recuento))->assertSessionHasErrors('recuento');
    }
}
