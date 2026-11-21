<?php

namespace Tests\Feature\RevisionSeguridad;

use App\Models\Cliente;
use App\Models\Permission;
use App\Models\Presupuesto;
use App\Models\Producto;
use App\Models\Role;
use App\Models\User;
use App\Models\Venta;
use Tests\TestCase;

/**
 * Revisión adversaria final: lo que una persona con mala intención todavía podía hacer
 * después de los arreglos H/M/B.
 */
class RevisionFinalTest extends TestCase
{
    private function usuarioCon(string ...$claves): User
    {
        $rol = Role::create(['nombre' => 'Rol '.uniqid()]);
        $rol->sincronizarPermisos(collect($claves)->map(fn ($c) => Permission::firstOrCreate(['clave' => $c])->id)->all());
        Role::olvidarClaves($rol->id);

        return User::factory()->create(['role_id' => $rol->id]);
    }

    private function presupuestoDe(User $u, Producto $p, float $precio): Presupuesto
    {
        $this->actingAs($u)->post(route('presupuestos.store'), [
            'cliente_id' => Cliente::factory()->create(['limite_credito' => 0])->id,
            'fecha'      => now()->toDateString(),
            'detalles'   => [['producto_id' => $p->id, 'cantidad' => 1, 'precio' => $precio]],
        ])->assertSessionHasNoErrors();

        return Presupuesto::latest('id')->firstOrFail();
    }

    public function test_el_dashboard_no_muestra_ganancia_ni_margen_a_quien_no_ve_costos(): void
    {
        $producto = Producto::factory()->create(['stock' => 50, 'precio_compra' => 10, 'precio_venta' => 100]);
        $dueño = $this->usuarioCon('dashboard.ver', 'reportes.ver', 'ventas.ver', 'ventas.crear');
        $vendedor = $this->usuarioCon('dashboard.ver');
        $this->actingAs($dueño)->post(route('ventas.store'), [
            'cliente_id' => Cliente::factory()->create()->id, 'fecha' => now()->toDateString(), 'estado' => 'completada',
            'detalles'   => [['producto_id' => $producto->id, 'cantidad' => 2, 'precio' => 100]],
        ])->assertSessionHasNoErrors();

        $this->actingAs($dueño)->get(route('dashboard'))->assertOk()->assertSee('Ganancia del Mes')->assertSee('Margen de Ganancia');

        $this->actingAs($vendedor)->get(route('dashboard'))->assertOk()
            ->assertDontSee('Ganancia del Mes')
            ->assertDontSee('Margen de Ganancia')
            ->assertDontSee('Más Rentables');
    }

    public function test_un_presupuesto_no_se_convierte_dos_veces(): void
    {
        $producto = Producto::factory()->create(['stock' => 50, 'precio_venta' => 100]);
        $usuario = $this->usuarioCon('presupuestos.ver', 'presupuestos.crear', 'presupuestos.convertir', 'ventas.ver', 'ventas.editar');
        $presupuesto = $this->presupuestoDe($usuario, $producto, 100);

        $this->actingAs($usuario)->post(route('presupuestos.convertir', $presupuesto))->assertRedirect();
        $this->actingAs($usuario)->post(route('presupuestos.convertir', $presupuesto))->assertSessionHasErrors();

        $this->assertSame(1, Venta::count(), 'Un presupuesto convertido dos veces duplica la venta.');
    }

    public function test_la_venta_nacida_de_un_presupuesto_se_confirma_a_precio_de_lista(): void
    {
        $producto = Producto::factory()->create(['stock' => 50, 'precio_venta' => 500]);
        $vendedor = $this->usuarioCon('presupuestos.ver', 'presupuestos.crear', 'presupuestos.convertir', 'ventas.ver', 'ventas.editar');
        $presupuesto = $this->presupuestoDe($vendedor, $producto, 1);

        $this->actingAs($vendedor)->post(route('presupuestos.convertir', $presupuesto));
        $venta = Venta::firstOrFail();

        // Confirmarla (editar a «completada») re-precia: el vendedor no cobra 1 por algo de 500.
        $this->actingAs($vendedor)->put(route('ventas.update', $venta), [
            'cliente_id' => $venta->cliente_id, 'fecha' => now()->toDateString(), 'estado' => 'completada',
            'detalles'   => [['producto_id' => $producto->id, 'cantidad' => 1, 'precio' => 1]],
        ])->assertSessionHasNoErrors();

        $this->assertEquals(500, $venta->fresh()->detalles()->first()->precio);
    }
}
