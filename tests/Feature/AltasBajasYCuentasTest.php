<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Deposito;
use App\Models\MetodoPago;
use App\Models\Notificacion;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Role;
use App\Models\Tarea;
use App\Models\UnidadMedida;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El recorrido que hace cualquier persona que prueba el sistema: dar de alta,
 * editar y dar de baja cada cosa, cobrar y pagar, y cerrar su propia cuenta.
 * Ninguno de estos caminos tenía un test, y varios terminaban en un error 500.
 */
class AltasBajasYCuentasTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role_id' => $this->rolAdministrador()->id]);
        $this->actingAs($this->admin);
    }

    public function test_producto_alta_edicion_duplicado_y_baja(): void
    {
        $categoria = Categoria::factory()->create();

        $this->post(route('productos.store'), [
            'nombre'        => 'Gaseosa', 'codigo' => 'GAS-1', 'categoria_id' => $categoria->id,
            'precio_compra' => 100, 'precio_venta' => 180, 'stock_minimo' => 5,
        ])->assertSessionHasNoErrors()->assertRedirect();

        $producto = Producto::where('codigo', 'GAS-1')->firstOrFail();

        $this->put(route('productos.update', $producto), [
            'nombre'        => 'Gaseosa 2L', 'codigo' => 'GAS-1', 'categoria_id' => $categoria->id,
            'precio_compra' => 110, 'precio_venta' => 190, 'stock_minimo' => 5, 'estado' => 'activo',
        ])->assertSessionHasNoErrors();
        $this->assertSame('Gaseosa 2L', $producto->fresh()->nombre);

        $this->post(route('productos.duplicar', $producto))->assertRedirect();
        $this->assertSame(2, Producto::where('nombre', 'like', 'Gaseosa 2L%')->count());

        $this->delete(route('productos.destroy', $producto))->assertRedirect();
        $this->assertSame('eliminado', $producto->fresh()->estado);
    }

    public function test_cliente_alta_edicion_y_baja(): void
    {
        $this->post(route('clientes.store'), [
            'nombre' => 'Ana', 'apellido' => 'Paz', 'estado' => 'activo', 'documento' => '30111222',
        ])->assertSessionHasNoErrors();
        $cliente = Cliente::firstOrFail();

        $this->put(route('clientes.update', $cliente), [
            'nombre' => 'Ana', 'apellido' => 'Paz Ruiz', 'estado' => 'activo', 'limite_credito' => 5000,
        ])->assertSessionHasNoErrors();
        $this->assertSame('Paz Ruiz', $cliente->fresh()->apellido);

        $this->delete(route('clientes.destroy', $cliente))->assertRedirect();
        $this->assertNotSame('activo', $cliente->fresh()->estado);
    }

    public function test_categoria_proveedor_y_unidad_alta_edicion_y_baja(): void
    {
        $this->post(route('categorias.store'), ['nombre' => 'Bebidas', 'estado' => 1])->assertSessionHasNoErrors();
        $categoria = Categoria::where('nombre', 'Bebidas')->firstOrFail();
        $this->put(route('categorias.update', $categoria), ['nombre' => 'Bebidas frías', 'estado' => 1])->assertSessionHasNoErrors();
        $this->delete(route('categorias.destroy', $categoria))->assertRedirect();

        $this->post(route('proveedores.store'), ['nombre' => 'Distri', 'cuit' => '30111111118'])->assertSessionHasNoErrors();
        $proveedor = Proveedor::firstOrFail();
        $this->put(route('proveedores.update', $proveedor), ['nombre' => 'Distri SA', 'estado' => 'activo'])->assertSessionHasNoErrors();
        $this->delete(route('proveedores.destroy', $proveedor))->assertRedirect();

        $this->post(route('unidades-medida.store'), ['nombre' => 'Kilo', 'abreviacion' => 'kg', 'estado' => 1])->assertSessionHasNoErrors();
        $unidad = UnidadMedida::firstOrFail();
        $this->put(route('unidades-medida.update', $unidad), ['nombre' => 'Kilogramo', 'abreviacion' => 'kg', 'estado' => 1])->assertSessionHasNoErrors();
        $this->delete(route('unidades-medida.destroy', $unidad))->assertSuccessful();
    }

    public function test_tareas_depositos_y_metodos_de_pago(): void
    {
        $this->post(route('tareas.store'), [
            'titulo' => 'Llamar', 'prioridad' => 'alta', 'estado' => 'pendiente', 'fecha_limite' => now()->addDay()->toDateString(),
        ])->assertSessionHasNoErrors();
        $tarea = Tarea::firstOrFail();
        $this->patch(route('tareas.cambiar-estado', [$tarea, 'completada']))->assertRedirect();
        $this->assertSame('completada', $tarea->fresh()->estado);
        $this->delete(route('tareas.destroy', $tarea))->assertRedirect();

        $this->post(route('depositos.store'), ['nombre' => 'Sucursal'])->assertSessionHasNoErrors();
        $deposito = Deposito::where('nombre', 'Sucursal')->firstOrFail();
        $this->put(route('depositos.update', $deposito), ['nombre' => 'Sucursal Norte'])->assertSessionHasNoErrors();
        $this->delete(route('depositos.destroy', $deposito))->assertRedirect();

        $this->post(route('metodos-pago.store'), ['nombre' => 'Vale', 'codigo' => 'vale', 'activo' => 1])->assertSessionHasNoErrors();
        $metodo = MetodoPago::where('codigo', 'vale')->firstOrFail();
        $this->put(route('metodos-pago.update', $metodo), ['nombre' => 'Vale de compra', 'codigo' => 'vale', 'activo' => 1])->assertSessionHasNoErrors();
        $this->delete(route('metodos-pago.destroy', $metodo))->assertSuccessful();
    }

    public function test_usuarios_alta_rol_baja_y_reactivacion(): void
    {
        $vendedor = Role::create(['nombre' => 'Vendedor']);

        $this->post(route('usuarios.store'), [
            'name'     => 'Beto', 'email' => 'beto@x.com', 'role_id' => $vendedor->id,
            'password' => 'clave-segura-1', 'password_confirmation' => 'clave-segura-1',
        ])->assertSessionHasNoErrors();
        $beto = User::where('email', 'beto@x.com')->firstOrFail();

        $this->put(route('usuarios.update', $beto), [
            'name' => 'Beto Gómez', 'email' => 'beto@x.com', 'role_id' => $vendedor->id, 'activo' => 1,
        ])->assertSessionHasNoErrors();
        $this->put(route('usuarios.asignar-rol', $beto), ['role_id' => $vendedor->id])->assertSessionHasNoErrors();

        $this->delete(route('usuarios.destroy', $beto))->assertRedirect();
        $this->assertNull(User::find($beto->id));

        // Con historial no se borra: se desactiva y se puede reactivar.
        $otro = User::factory()->create(['role_id' => $vendedor->id]);
        Venta::create([
            'numero'   => 'VTA-X', 'cliente_id' => Cliente::factory()->create()->id, 'fecha' => now(),
            'subtotal' => 1, 'total' => 1, 'total_final' => 1, 'estado' => 'completada', 'user_id' => $otro->id,
        ]);
        $this->delete(route('usuarios.destroy', $otro))->assertRedirect();
        $this->assertFalse((bool) $otro->fresh()->activo);
        $this->post(route('usuarios.reactivar', $otro))->assertRedirect();
        $this->assertTrue((bool) $otro->fresh()->activo);
    }

    public function test_cerrar_la_propia_cuenta_respeta_el_historial_y_al_ultimo_administrador(): void
    {
        // El único administrador no se puede ir: el sistema quedaría sin dueño.
        $this->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('error');
        $this->assertNotNull($this->admin->fresh());

        // Un usuario con ventas se desactiva en vez de reventar por la clave foránea.
        $rol = Role::create(['nombre' => 'Vendedor']);
        $vendedor = User::factory()->create(['role_id' => $rol->id]);
        Venta::create([
            'numero'   => 'VTA-Y', 'cliente_id' => Cliente::factory()->create()->id, 'fecha' => now(),
            'subtotal' => 1, 'total' => 1, 'total_final' => 1, 'estado' => 'completada', 'user_id' => $vendedor->id,
        ]);

        $this->actingAs($vendedor)->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertRedirect('/');
        $this->assertFalse((bool) $vendedor->fresh()->activo);
        $this->assertGuest();

        // Uno sin historial sí se borra.
        $sinHistorial = User::factory()->create(['role_id' => $rol->id]);
        $this->actingAs($sinHistorial)->delete(route('profile.destroy'), ['password' => 'password'])->assertRedirect('/');
        $this->assertNull(User::find($sinHistorial->id));
    }

    public function test_cobros_y_pagos_en_cuenta_corriente(): void
    {
        $producto = Producto::factory()->create(['stock' => 20]);
        $cliente = Cliente::factory()->create();
        $this->post(route('ventas.store'), [
            'cliente_id' => $cliente->id, 'fecha' => now()->toDateString(), 'estado' => 'completada',
            'detalles'   => [['producto_id' => $producto->id, 'cantidad' => 1, 'precio' => 1000]],
        ])->assertSessionHasNoErrors();
        $venta = Venta::firstOrFail();
        $efectivo = MetodoPago::firstOrCreate(['codigo' => 'efectivo'], ['nombre' => 'Efectivo', 'activo' => true]);

        $this->post(route('cuentas.cliente.cobrar', $cliente), ['metodo_pago_id' => $efectivo->id, 'monto' => 400])
            ->assertSessionHasNoErrors();
        $this->assertEquals(400, $venta->pagos()->sum('monto'));

        $this->post(route('cuentas.cliente.cobrar', $cliente), ['venta_id' => $venta->id, 'metodo_pago_id' => $efectivo->id, 'monto' => 100])
            ->assertSessionHasNoErrors();
        $this->assertEquals(500, $venta->pagos()->sum('monto'));

        // Cobrar de más no se acepta.
        $this->post(route('cuentas.cliente.cobrar', $cliente), ['venta_id' => $venta->id, 'metodo_pago_id' => $efectivo->id, 'monto' => 999999])
            ->assertSessionHasErrors('monto');
    }

    public function test_notificaciones_solo_las_propias(): void
    {
        $mia = Notificacion::create(['user_id' => $this->admin->id, 'tipo' => 'info', 'titulo' => 'Hola', 'mensaje' => 'x', 'leida' => false]);
        $ajena = Notificacion::create(['user_id' => User::factory()->create()->id, 'tipo' => 'info', 'titulo' => 'Otra', 'mensaje' => 'x', 'leida' => false]);

        $this->get(route('notificaciones.index'))->assertOk()->assertSee('Hola')->assertDontSee('Otra');
        $this->get(route('notificaciones.count'))->assertOk();
        $this->patch(route('notificaciones.marcar-leida', $ajena))->assertForbidden();
        $this->patch(route('notificaciones.marcar-leida', $mia))->assertRedirect();
        $this->assertTrue((bool) $mia->fresh()->leida);
        $this->patch(route('notificaciones.leer-todas'))->assertRedirect();
    }

    public function test_las_exportaciones_descargan(): void
    {
        Producto::factory()->count(3)->create();

        $this->get(route('exportar.productos'))->assertOk();
        $this->get(route('exportar.ventas'))->assertOk();
    }
}
