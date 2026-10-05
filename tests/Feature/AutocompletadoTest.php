<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Role;
use App\Models\User;
use App\Models\Venta;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Los formularios de carga buscan contra endpoints en vez de traer el catálogo
 * entero embebido en el HTML.
 *
 * Con los datos de demostración la diferencia no se nota; con un catálogo real
 * de miles de productos, el formulario pasaba de megas a kilobytes.
 */
class AutocompletadoTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(PermissionSeeder::class);

        return User::factory()->create(['role_id' => $this->rolAdministrador()->id]);
    }

    public function test_el_formulario_de_venta_no_trae_el_catalogo_embebido(): void
    {
        $admin = $this->admin();
        Producto::factory()->count(30)->create(['nombre' => 'Producto que no debería viajar']);
        Cliente::factory()->count(30)->create();

        $this->actingAs($admin)->get(route('ventas.create'))
            ->assertOk()
            ->assertDontSee('Producto que no debería viajar');
    }

    public function test_el_formulario_de_edicion_muestra_lo_ya_elegido(): void
    {
        $admin = $this->admin();
        $cliente = Cliente::factory()->create(['nombre' => 'Marcela', 'apellido' => 'Ríos']);
        $producto = Producto::factory()->create(['nombre' => 'Taladro percutor', 'stock' => 10]);

        $this->actingAs($admin)->post(route('ventas.store'), [
            'cliente_id' => $cliente->id,
            'fecha'      => now()->toDateString(),
            'estado'     => 'pendiente',
            'detalles'   => [['producto_id' => $producto->id, 'cantidad' => 1, 'precio' => 100]],
        ]);

        $venta = Venta::latest('id')->first();

        $this->actingAs($admin)->get(route('ventas.edit', $venta))
            ->assertOk()
            ->assertSee('Ríos, Marcela')
            ->assertSee('Taladro percutor');
    }

    public function test_buscar_productos_devuelve_coincidencias_por_nombre_y_por_codigo(): void
    {
        $admin = $this->admin();
        $producto = Producto::factory()->create(['nombre' => 'Destornillador Phillips', 'codigo' => 'DEST-01']);
        Producto::factory()->create(['nombre' => 'Otra cosa']);

        $this->actingAs($admin)->getJson(route('productos.buscar', ['q' => 'destorni']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['id' => $producto->id]);

        $this->actingAs($admin)->getJson(route('productos.buscar', ['q' => 'DEST-01']))
            ->assertOk()
            ->assertJsonFragment(['id' => $producto->id]);
    }

    public function test_buscar_productos_ignora_los_eliminados(): void
    {
        $admin = $this->admin();
        Producto::factory()->create(['nombre' => 'Fantasma', 'estado' => 'eliminado']);

        $this->actingAs($admin)->getJson(route('productos.buscar', ['q' => 'fantasma']))
            ->assertOk()
            ->assertJsonCount(0);
    }

    public function test_buscar_clientes_devuelve_apellido_y_documento(): void
    {
        $admin = $this->admin();
        $cliente = Cliente::factory()->create([
            'nombre'    => 'Lucía',
            'apellido'  => 'Fernández',
            'documento' => '30551603',
        ]);

        $this->actingAs($admin)->getJson(route('clientes.buscar', ['q' => 'fern']))
            ->assertOk()
            ->assertJsonFragment([
                'id'       => $cliente->id,
                'etiqueta' => 'Fernández, Lucía',
                'detalle'  => 'Doc. 30551603',
            ]);
    }

    public function test_buscar_clientes_ignora_los_archivados(): void
    {
        $admin = $this->admin();
        Cliente::factory()->create(['apellido' => 'Archivado', 'estado' => 'archivado']);

        $this->actingAs($admin)->getJson(route('clientes.buscar', ['q' => 'archivado']))
            ->assertOk()
            ->assertJsonCount(0);
    }

    public function test_buscar_proveedores_ignora_los_archivados(): void
    {
        $admin = $this->admin();
        $activo = Proveedor::factory()->create(['nombre' => 'Distribuidora Norte']);
        Proveedor::factory()->archivado()->create(['nombre' => 'Distribuidora Vieja']);

        $this->actingAs($admin)->getJson(route('proveedores.buscar', ['q' => 'distribuidora']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['id' => $activo->id]);
    }

    public function test_una_busqueda_vacia_no_devuelve_el_catalogo_entero(): void
    {
        $admin = $this->admin();
        Producto::factory()->count(5)->create();

        $this->actingAs($admin)->getJson(route('productos.buscar', ['q' => '']))
            ->assertOk()
            ->assertJsonCount(0);
    }

    public function test_los_buscadores_exigen_permiso_de_lectura(): void
    {
        $this->seed(PermissionSeeder::class);

        $sinPermisos = User::factory()->create(['role_id' => Role::create(['nombre' => 'Sin nada'])->id]);

        $this->actingAs($sinPermisos)->getJson(route('productos.buscar', ['q' => 'a']))->assertForbidden();
        $this->actingAs($sinPermisos)->getJson(route('clientes.buscar', ['q' => 'a']))->assertForbidden();
        $this->actingAs($sinPermisos)->getJson(route('proveedores.buscar', ['q' => 'a']))->assertForbidden();
    }
}
