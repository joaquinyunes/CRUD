<?php

namespace Tests\Feature\RevisionSeguridad;

use App\Exports\ProductosExport;
use App\Exports\VentasExport;
use App\Models\Cliente;
use App\Models\Permission;
use App\Models\Producto;
use App\Models\Role;
use App\Models\User;
use App\Models\Venta;
use App\Support\CeldaSegura;
use Database\Seeders\DatabaseSeeder;
use Tests\TestCase;

/**
 * M1 y M8: las exportaciones respetan lo que cada usuario puede ver, el costo no sale
 * para quien no tiene reportes y una celda no se ejecuta como fórmula en Excel.
 */
class ExportesYCostosTest extends TestCase
{
    private function usuarioCon(string ...$claves): User
    {
        $rol = Role::create(['nombre' => 'Rol '.uniqid()]);
        $rol->sincronizarPermisos(collect($claves)->map(fn ($c) => Permission::firstOrCreate(['clave' => $c])->id)->all());
        Role::olvidarClaves($rol->id);

        return User::factory()->create(['role_id' => $rol->id]);
    }

    private function vender(User $u, Producto $p, string $nombreCliente = 'Cliente'): Venta
    {
        $this->actingAs($u)->post(route('ventas.store'), [
            'cliente_id' => Cliente::factory()->create(['nombre' => $nombreCliente, 'limite_credito' => 1000000])->id,
            'fecha'      => now()->toDateString(),
            'estado'     => 'completada',
            'detalles'   => [['producto_id' => $p->id, 'cantidad' => 1, 'precio' => 100]],
        ])->assertSessionHasNoErrors();

        return Venta::latest('id')->firstOrFail();
    }

    public function test_la_exportacion_de_ventas_solo_trae_las_propias(): void
    {
        $producto = Producto::factory()->create(['stock' => 50]);
        $ana = $this->usuarioCon('ventas.ver', 'ventas.crear', 'ventas.exportar');
        $beto = $this->usuarioCon('ventas.ver', 'ventas.crear', 'ventas.exportar');
        $supervisor = $this->usuarioCon('ventas.ver', 'ventas.gestionar_todas');
        $deAna = $this->vender($ana, $producto);
        $this->vender($beto, $producto);

        $this->assertSame([$deAna->numero], (new VentasExport($ana))->collection()->pluck('numero')->all());
        $this->assertCount(2, (new VentasExport($supervisor))->collection());
    }

    public function test_el_costo_no_sale_para_quien_no_tiene_reportes(): void
    {
        Producto::factory()->create(['precio_compra' => 777, 'precio_venta' => 999]);

        $sin = new ProductosExport(false);
        $con = new ProductosExport(true);
        $producto = $sin->collection()->first();

        $this->assertNotContains('Precio Compra', $sin->headings());
        $this->assertContains('Precio Compra', $con->headings());
        $this->assertNotContains(777.0, array_map('floatval', $sin->map($producto)));
        $this->assertCount(count($sin->headings()), $sin->map($producto));
        $this->assertCount(count($con->headings()), $con->map($producto));
    }

    public function test_la_pantalla_y_la_api_de_productos_ocultan_el_costo(): void
    {
        Producto::factory()->create(['nombre' => 'Martillo', 'precio_compra' => 777.00, 'precio_venta' => 999.00]);
        $vendedor = $this->usuarioCon('productos.ver');
        $dueño = $this->usuarioCon('productos.ver', 'reportes.ver');

        $this->actingAs($vendedor)->get(route('productos.index'))->assertOk()->assertDontSee('P. Compra')->assertDontSee('777,00');
        $this->actingAs($dueño)->get(route('productos.index'))->assertOk()->assertSee('P. Compra')->assertSee('777,00');

        $this->actingAs($vendedor, 'sanctum')->getJson('/api/productos')->assertOk()->assertJsonMissingPath('data.0.precio_compra');
        $this->actingAs($dueño, 'sanctum')->getJson('/api/productos')->assertOk()->assertJsonPath('data.0.precio_compra', '777.00');
    }

    public function test_el_rol_vendedor_ya_no_trae_reportes(): void
    {
        $this->seed(DatabaseSeeder::class);

        $claves = Role::clavesDe(Role::where('nombre', Role::VENDEDOR)->value('id'));

        $this->assertNotContains('reportes.ver', $claves);
        $this->assertNotContains('reportes.exportar', $claves);
    }

    public function test_una_celda_que_empieza_con_igual_no_se_ejecuta_como_formula(): void
    {
        $this->assertSame("'=HYPERLINK(\"http://x\")", CeldaSegura::limpiar('=HYPERLINK("http://x")'));
        $this->assertSame("'@SUM(A1)", CeldaSegura::limpiar('@SUM(A1)'));
        $this->assertSame("'-2+3", CeldaSegura::limpiar('-2+3'));
        $this->assertSame('Normal', CeldaSegura::limpiar('Normal'));
        $this->assertSame(12.5, CeldaSegura::limpiar(12.5));

        $producto = Producto::factory()->create(['stock' => 50]);
        $admin = User::factory()->create(['role_id' => $this->rolAdministrador()->id]);
        $venta = $this->vender($admin, $producto, '=cmd|calc');
        $fila = (new VentasExport($admin))->map($venta->load('cliente', 'user'));
        $this->assertStringStartsWith("'=cmd", $fila[1]);
    }

    public function test_el_formato_de_exportacion_va_en_lista_blanca(): void
    {
        $admin = User::factory()->create(['role_id' => $this->rolAdministrador()->id]);

        $this->actingAs($admin)->get(route('exportar.ventas', ['formato' => 'php']))->assertSessionHasErrors('formato');
        $this->actingAs($admin)->get(route('exportar.ventas', ['formato' => 'csv']))->assertOk();
    }
}
