<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Models\Venta;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * La API y la interfaz web son dos puertas a la misma aplicación.
 *
 * Antes cada una tenía su propia implementación de "crear una venta", y la de
 * la API numeraba los comprobantes con MAX(id) + 1: dos ventas cargadas por
 * distintas puertas podían salir con el mismo número.
 */
class ApiYWebComparteReglasTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(PermissionSeeder::class);

        return User::factory()->create(['role_id' => $this->rolAdministrador()->id]);
    }

    /** @return array<string,mixed> */
    private function datosDeVenta(Cliente $cliente, Producto $producto): array
    {
        return [
            'cliente_id' => $cliente->id,
            'fecha'      => now()->toDateString(),
            'estado'     => 'completada',
            'detalles'   => [['producto_id' => $producto->id, 'cantidad' => 1, 'precio' => 100]],
        ];
    }

    public function test_las_dos_puertas_comparten_la_misma_serie_de_numeros(): void
    {
        $admin = $this->admin();
        $cliente = Cliente::factory()->create();
        $producto = Producto::factory()->create(['stock' => 100]);

        $this->actingAs($admin)->post(route('ventas.store'), $this->datosDeVenta($cliente, $producto));

        Sanctum::actingAs($admin);
        $this->postJson('/api/ventas', $this->datosDeVenta($cliente, $producto))->assertCreated();

        $this->actingAs($admin)->post(route('ventas.store'), $this->datosDeVenta($cliente, $producto));

        $numeros = Venta::orderBy('id')->pluck('numero')->all();

        $this->assertSame(['VTA-00001', 'VTA-00002', 'VTA-00003'], $numeros);
    }

    public function test_la_api_respeta_el_prefijo_y_los_digitos_configurados(): void
    {
        $admin = $this->admin();
        Setting::establecer('ventas_prefijo_numero', 'FAC');
        Setting::establecer('ventas_cantidad_digitos', '7');

        Sanctum::actingAs($admin);
        $this->postJson('/api/ventas', $this->datosDeVenta(
            Cliente::factory()->create(),
            Producto::factory()->create(['stock' => 10])
        ))->assertCreated();

        $this->assertSame('FAC-0000001', Venta::first()->numero);
    }

    public function test_la_api_aplica_el_limite_de_credito_del_cliente(): void
    {
        $admin = $this->admin();
        $cliente = Cliente::factory()->create(['limite_credito' => 50]);

        Sanctum::actingAs($admin);

        $this->postJson('/api/ventas', $this->datosDeVenta(
            $cliente,
            Producto::factory()->create(['stock' => 10])
        ))->assertStatus(422)->assertJsonValidationErrors('cliente_id');

        $this->assertDatabaseCount('ventas', 0);
    }

    public function test_la_api_no_vende_sobre_un_cliente_archivado(): void
    {
        $admin = $this->admin();
        $cliente = Cliente::factory()->create(['estado' => 'archivado']);

        Sanctum::actingAs($admin);

        $this->postJson('/api/ventas', $this->datosDeVenta(
            $cliente,
            Producto::factory()->create(['stock' => 10])
        ))->assertStatus(422)->assertJsonValidationErrors('cliente_id');
    }

    public function test_la_api_no_permite_sobrevender(): void
    {
        $admin = $this->admin();
        $producto = Producto::factory()->create(['stock' => 2]);

        Sanctum::actingAs($admin);

        $this->postJson('/api/ventas', [
            'cliente_id' => Cliente::factory()->create()->id,
            'fecha'      => now()->toDateString(),
            'estado'     => 'completada',
            'detalles'   => [['producto_id' => $producto->id, 'cantidad' => 5, 'precio' => 100]],
        ])->assertStatus(422)->assertJsonValidationErrors('detalles');

        $this->assertSame(2, $producto->fresh()->stock);
    }

    public function test_la_api_aplica_el_descuento_que_antes_ignoraba(): void
    {
        $admin = $this->admin();
        Setting::establecer('sistema_impuesto_habilitado', '0');

        Sanctum::actingAs($admin);

        $this->postJson('/api/ventas', array_merge(
            $this->datosDeVenta(Cliente::factory()->create(), Producto::factory()->create(['stock' => 10])),
            ['descuento_tipo' => 'porcentaje', 'descuento' => 10]
        ))->assertCreated();

        $venta = Venta::first();

        $this->assertSame('10.00', (string) $venta->descuento);
        $this->assertSame('90.00', (string) $venta->total_final);
    }

    public function test_una_compra_por_api_comparte_la_serie_con_la_web(): void
    {
        $admin = $this->admin();
        $proveedor = Proveedor::factory()->create();
        $producto = Producto::factory()->create();

        $compra = [
            'proveedor_id' => $proveedor->id,
            'fecha'        => now()->toDateString(),
            'estado'       => 'completada',
            'detalles'     => [['producto_id' => $producto->id, 'cantidad' => 2, 'precio' => 50]],
        ];

        $this->actingAs($admin)->post(route('compras.store'), $compra);

        Sanctum::actingAs($admin);
        $this->postJson('/api/compras', $compra)->assertCreated();

        $this->assertSame(
            ['COM-00001', 'COM-00002'],
            Compra::orderBy('id')->pluck('numero')->all()
        );
    }

    public function test_una_venta_por_api_queda_auditada(): void
    {
        $admin = $this->admin();

        Sanctum::actingAs($admin);
        $this->postJson('/api/ventas', $this->datosDeVenta(
            Cliente::factory()->create(),
            Producto::factory()->create(['stock' => 10])
        ))->assertCreated();

        $this->assertDatabaseHas('auditoria', [
            'modelo_afectado' => Venta::class,
            'accion'          => 'created',
            'user_id'         => $admin->id,
        ]);
    }

    public function test_un_usuario_de_solo_lectura_sigue_sin_poder_vender_por_api(): void
    {
        $this->seed(PermissionSeeder::class);

        $rol = Role::create(['nombre' => 'Solo Lectura']);
        $usuario = User::factory()->create(['role_id' => $rol->id]);

        Sanctum::actingAs($usuario);

        $this->postJson('/api/ventas', $this->datosDeVenta(
            Cliente::factory()->create(),
            Producto::factory()->create(['stock' => 10])
        ))->assertStatus(403);
    }
}
