<?php

namespace Tests\Feature;

use App\Models\CajaSesion;
use App\Models\Cliente;
use App\Models\MetodoPago;
use App\Models\Permission;
use App\Models\Producto;
use App\Models\ProductoCodigo;
use App\Models\Role;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role_id' => Role::create(['nombre' => 'Administrador'])->id]);
    }

    private function efectivo(): MetodoPago
    {
        return MetodoPago::create(['nombre' => 'Efectivo', 'codigo' => 'efectivo', 'activo' => true, 'permite_vuelto' => true, 'orden' => 1]);
    }

    private function abrirCaja(User $u): CajaSesion
    {
        $this->actingAs($u)->post(route('caja.abrir'), ['monto_inicial' => 1000]);

        return CajaSesion::first();
    }

    public function test_buscar_por_codigo_de_barras_secundario(): void
    {
        $admin = $this->admin();
        $producto = Producto::factory()->create(['nombre' => 'Gaseosa 500', 'stock' => 10]);
        ProductoCodigo::create(['producto_id' => $producto->id, 'codigo' => '7791234567890', 'factor' => 1]);

        $this->actingAs($admin)
            ->getJson(route('pos.buscar', ['q' => '7791234567890']))
            ->assertOk()
            ->assertJsonFragment(['id' => $producto->id, 'nombre' => 'Gaseosa 500']);
    }

    public function test_venta_de_mostrador_descuenta_stock_y_registra_caja(): void
    {
        $admin = $this->admin();
        $efectivo = $this->efectivo();
        $sesion = $this->abrirCaja($admin);
        $producto = Producto::factory()->create(['stock' => 10, 'precio_venta' => 100]);

        $resp = $this->actingAs($admin)->postJson(route('pos.store'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 2, 'precio' => 100]],
            'pagos' => [['metodo_pago_id' => $efectivo->id, 'monto' => 300]],
            'recibido' => 300,
        ]);

        // 2 x 100 = 200 + 21% = 242
        $resp->assertOk();
        $this->assertEquals(242, $resp->json('total'));
        $this->assertEquals(58, $resp->json('vuelto'));

        $this->assertSame(8, $producto->fresh()->stock);
        $venta = Venta::first();
        $this->assertSame('completada', $venta->estado);
        $this->assertEquals($sesion->id, $venta->caja_sesion_id);
        // Efectivo neto en caja = 242 (recibido 300 - vuelto 58)
        $this->assertEquals(242, $sesion->fresh()->totalIngresos());
    }

    public function test_no_se_puede_vender_sin_caja_abierta(): void
    {
        $admin = $this->admin();
        $efectivo = $this->efectivo();
        $producto = Producto::factory()->create(['stock' => 5]);

        $this->actingAs($admin)->postJson(route('pos.store'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1, 'precio' => 100]],
            'pagos' => [['metodo_pago_id' => $efectivo->id, 'monto' => 121]],
        ])->assertStatus(422)->assertJsonFragment(['message' => 'Abrí la caja antes de vender.']);
    }

    public function test_pago_insuficiente_sin_cuenta_corriente_falla(): void
    {
        $admin = $this->admin();
        $efectivo = $this->efectivo();
        $this->abrirCaja($admin);
        $producto = Producto::factory()->create(['stock' => 5, 'precio_venta' => 100]);

        $this->actingAs($admin)->postJson(route('pos.store'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1, 'precio' => 100]],
            'pagos' => [['metodo_pago_id' => $efectivo->id, 'monto' => 50]],
        ])->assertStatus(422);
    }

    public function test_vuelto_se_calcula_desde_el_efectivo_entregado(): void
    {
        $admin = $this->admin();
        $efectivo = $this->efectivo();
        $sesion = $this->abrirCaja($admin);
        $producto = Producto::factory()->create(['stock' => 10, 'precio_venta' => 100]);

        // total 121, el cliente entrega 200 -> vuelto 79, a caja entran 121
        $this->actingAs($admin)->postJson(route('pos.store'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1, 'precio' => 100]],
            'pagos' => [['metodo_pago_id' => $efectivo->id, 'monto' => 121]],
            'recibido' => 200,
        ])->assertOk();

        $venta = Venta::first();
        $this->assertEquals(200, $venta->recibido);
        $this->assertEquals(79, $venta->vuelto);
        $this->assertEquals('pagado', $venta->estado_pago);
        $this->assertEquals(121, $sesion->fresh()->totalIngresos());
    }

    public function test_cuenta_corriente_sin_cliente_falla(): void
    {
        $admin = $this->admin();
        $cc = MetodoPago::create(['nombre' => 'Cta Cte', 'codigo' => 'cuenta_corriente', 'activo' => true, 'orden' => 6]);
        $this->abrirCaja($admin);
        $producto = Producto::factory()->create(['stock' => 5, 'precio_venta' => 100]);

        $this->actingAs($admin)->postJson(route('pos.store'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1, 'precio' => 100]],
            'pagos' => [['metodo_pago_id' => $cc->id, 'monto' => 121]],
        ])->assertStatus(422)->assertJsonFragment(['message' => 'Cuenta corriente requiere un cliente.']);
    }

    public function test_venta_a_cuenta_corriente_queda_impaga(): void
    {
        $admin = $this->admin();
        $cc = MetodoPago::create(['nombre' => 'Cta Cte', 'codigo' => 'cuenta_corriente', 'activo' => true, 'orden' => 6]);
        $this->abrirCaja($admin);
        $cliente = Cliente::factory()->create(['limite_credito' => 100000]);
        $producto = Producto::factory()->create(['stock' => 5, 'precio_venta' => 100]);

        $this->actingAs($admin)->postJson(route('pos.store'), [
            'cliente_id' => $cliente->id,
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1, 'precio' => 100]],
            'pagos' => [['metodo_pago_id' => $cc->id, 'monto' => 121]],
        ])->assertOk();

        $venta = Venta::first();
        $this->assertEquals(0, $venta->pagado);
        $this->assertEquals('impago', $venta->estado_pago);
    }

    public function test_cobro_split_efectivo_mas_debito(): void
    {
        $admin = $this->admin();
        $efectivo = $this->efectivo();
        $debito = MetodoPago::create(['nombre' => 'Débito', 'codigo' => 'debito', 'activo' => true, 'orden' => 2]);
        $sesion = $this->abrirCaja($admin);
        $producto = Producto::factory()->create(['stock' => 10, 'precio_venta' => 100]);

        $this->actingAs($admin)->postJson(route('pos.store'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1, 'precio' => 100]], // 121
            'pagos' => [
                ['metodo_pago_id' => $efectivo->id, 'monto' => 21],
                ['metodo_pago_id' => $debito->id, 'monto' => 100],
            ],
            'recibido' => 21,
        ])->assertOk();

        $venta = Venta::first();
        $this->assertSame('pagado', $venta->estado_pago);
        $this->assertEquals(21, $sesion->fresh()->totalIngresos());
        $this->assertCount(2, $venta->pagos);
    }

    /** Rol Empleado con permiso para vender y operar caja, pero SIN pos.supervisar. */
    private function empleado(): User
    {
        $rol = Role::create(['nombre' => 'Empleado']);
        $rol->permissions()->attach([
            Permission::create(['clave' => 'pos.usar'])->id,
            Permission::create(['clave' => 'caja.operar'])->id,
        ]);

        return User::factory()->create(['role_id' => $rol->id]);
    }

    public function test_precio_manual_muy_por_debajo_de_lista_requiere_pin_de_supervisor(): void
    {
        $empleado = $this->empleado();
        $efectivo = $this->efectivo();
        $this->abrirCaja($empleado);
        $producto = Producto::factory()->create(['stock' => 10, 'precio_venta' => 100]);

        // Precio manual a $1 sobre un producto de $100 de lista: 99% de baja, muy por
        // encima del limite (10% por defecto) -> antes del fix esto pasaba sin pedir nada.
        $payload = [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1, 'precio' => 1, 'precio_manual' => true]],
            'pagos' => [['metodo_pago_id' => $efectivo->id, 'monto' => 1.21]],
        ];

        $this->actingAs($empleado)->postJson(route('pos.store'), $payload)
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Descuento alto: requiere PIN de un supervisor.']);
        $this->assertSame(0, Venta::count());

        $permiso = Permission::create(['clave' => 'pos.supervisar']);
        $supervisorRole = Role::create(['nombre' => 'Supervisor']);
        $supervisorRole->permissions()->attach($permiso->id);
        User::factory()->create(['role_id' => $supervisorRole->id, 'pin_supervisor' => '2468']);

        $this->actingAs($empleado)
            ->postJson(route('pos.store'), $payload + ['pin_supervisor' => '2468'])
            ->assertOk();
        $this->assertSame(1, Venta::count());
    }

    public function test_descuento_fijo_igual_al_subtotal_requiere_pin_de_supervisor(): void
    {
        $empleado = $this->empleado();
        $efectivo = $this->efectivo();
        $this->abrirCaja($empleado);
        $producto = Producto::factory()->create(['stock' => 10, 'precio_venta' => 100]);

        // descuento_tipo=fijo esquivaba el chequeo por completo antes del fix.
        $this->actingAs($empleado)->postJson(route('pos.store'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1, 'precio' => 100]],
            'descuento_tipo' => 'fijo',
            'descuento' => 100,
            'pagos' => [['metodo_pago_id' => $efectivo->id, 'monto' => 0.01]],
        ])->assertStatus(422)->assertJsonFragment(['message' => 'Descuento alto: requiere PIN de un supervisor.']);
        $this->assertSame(0, Venta::count());
    }
}
