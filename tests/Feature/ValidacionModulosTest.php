<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Deposito;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Las reglas de los módulos que antes validaban dentro del controller.
 *
 * Presupuestos y órdenes de compra comparten las reglas de las líneas con
 * ventas y compras, así que lo que vale para uno vale para todos.
 */
class ValidacionModulosTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(PermissionSeeder::class);

        return User::factory()->create(['role_id' => $this->rolAdministrador()->id]);
    }

    public function test_un_presupuesto_sin_lineas_se_rechaza(): void
    {
        $this->actingAs($this->admin())->post(route('presupuestos.store'), [
            'cliente_id' => Cliente::factory()->create()->id,
            'fecha'      => now()->toDateString(),
            'detalles'   => [],
        ])->assertSessionHasErrors('detalles');
    }

    public function test_un_presupuesto_no_acepta_un_descuento_mayor_al_cien_por_ciento(): void
    {
        $this->actingAs($this->admin())->post(route('presupuestos.store'), [
            'cliente_id'     => Cliente::factory()->create()->id,
            'fecha'          => now()->toDateString(),
            'detalles'       => [['producto_id' => Producto::factory()->create()->id, 'cantidad' => 1, 'precio' => 100]],
            'descuento_tipo' => 'porcentaje',
            'descuento'      => 120,
        ])->assertSessionHasErrors('descuento');
    }

    public function test_un_presupuesto_no_acepta_una_validez_absurda(): void
    {
        $this->actingAs($this->admin())->post(route('presupuestos.store'), [
            'cliente_id'   => Cliente::factory()->create()->id,
            'fecha'        => now()->toDateString(),
            'validez_dias' => 5000,
            'detalles'     => [['producto_id' => Producto::factory()->create()->id, 'cantidad' => 1, 'precio' => 100]],
        ])->assertSessionHasErrors('validez_dias');
    }

    public function test_una_orden_de_compra_no_se_entrega_antes_de_emitirse(): void
    {
        $this->actingAs($this->admin())->post(route('ordenes-compra.store'), [
            'proveedor_id'           => Proveedor::factory()->create()->id,
            'fecha'                  => now()->toDateString(),
            'fecha_entrega_estimada' => now()->subWeek()->toDateString(),
            'detalles'               => [['producto_id' => Producto::factory()->create()->id, 'cantidad' => 1, 'precio' => 100]],
        ])->assertSessionHasErrors('fecha_entrega_estimada');
    }

    public function test_una_orden_de_compra_no_acepta_cantidad_cero(): void
    {
        $this->actingAs($this->admin())->post(route('ordenes-compra.store'), [
            'proveedor_id' => Proveedor::factory()->create()->id,
            'fecha'        => now()->toDateString(),
            'detalles'     => [['producto_id' => Producto::factory()->create()->id, 'cantidad' => 0, 'precio' => 100]],
        ])->assertSessionHasErrors('detalles.0.cantidad');
    }

    public function test_un_movimiento_de_caja_de_cero_se_rechaza(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('caja.abrir'), ['monto_inicial' => 1000]);

        $this->actingAs($admin)->post(route('caja.movimiento'), [
            'tipo'     => 'ingreso',
            'concepto' => 'Nada',
            'monto'    => 0,
        ])->assertSessionHasErrors('monto');
    }

    public function test_no_se_transfiere_stock_al_mismo_deposito(): void
    {
        $admin = $this->admin();
        $deposito = Deposito::create(['nombre' => 'Central', 'es_principal' => true, 'estado' => true]);

        $this->actingAs($admin)->post(route('depositos.transferir'), [
            'producto_id' => Producto::factory()->create(['stock' => 10])->id,
            'origen_id'   => $deposito->id,
            'destino_id'  => $deposito->id,
            'cantidad'    => 1,
        ])->assertSessionHasErrors('destino_id');
    }

    public function test_una_tarea_nueva_no_se_puede_fechar_en_el_pasado(): void
    {
        $this->actingAs($this->admin())->post(route('tareas.store'), [
            'titulo'       => 'Llamar al proveedor',
            'prioridad'    => 'media',
            'estado'       => 'pendiente',
            'fecha_limite' => now()->subDay()->toDateString(),
        ])->assertSessionHasErrors('fecha_limite');
    }

    public function test_un_evento_no_puede_terminar_antes_de_empezar(): void
    {
        $this->actingAs($this->admin())->post(route('calendario.store'), [
            'titulo' => 'Inventario',
            'inicio' => now()->toDateTimeString(),
            'fin'    => now()->subHour()->toDateTimeString(),
        ])->assertSessionHasErrors('fin');
    }

    public function test_solo_se_adjuntan_los_tipos_de_archivo_permitidos(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())->post(route('archivos.store'), [
            'archivo' => UploadedFile::fake()->create('script.php', 10, 'application/x-php'),
        ])->assertSessionHasErrors('archivo');

        $this->assertDatabaseCount('archivos', 0);
    }

    public function test_un_metodo_de_pago_no_repite_codigo(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('metodos-pago.store'), [
            'nombre' => 'Efectivo',
            'codigo' => 'efectivo',
        ])->assertSessionHasNoErrors();

        $this->actingAs($admin)->post(route('metodos-pago.store'), [
            'nombre' => 'Caja',
            'codigo' => 'efectivo',
        ])->assertSessionHasErrors('codigo');

        $this->assertDatabaseCount('metodos_pago', 1);
    }

    public function test_un_rol_no_acepta_permisos_inventados(): void
    {
        $admin = $this->admin();
        $rol = Role::create(['nombre' => 'Depósito']);

        $this->actingAs($admin)->put(route('roles.update', $rol), [
            'permisos' => ['ventas.ver', 'inventado.total'],
        ])->assertSessionHasErrors('permisos.1');

        $this->assertCount(0, $rol->fresh()->permissions);
    }

    public function test_un_cobro_necesita_medio_de_pago_y_monto_positivo(): void
    {
        $admin = $this->admin();
        $cliente = Cliente::factory()->create();

        $this->actingAs($admin)
            ->post(route('cuentas.cliente.cobrar', $cliente), ['monto' => 0])
            ->assertSessionHasErrors(['metodo_pago_id', 'monto']);
    }
}
