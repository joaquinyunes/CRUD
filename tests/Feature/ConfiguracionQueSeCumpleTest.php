<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Devolucion;
use App\Models\Permission;
use App\Models\Producto;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * La pantalla de configuración ofrecía opciones que no hacían nada: apagar los
 * descuentos no se podía (una casilla destildada no viaja en el formulario) y el
 * límite de descuento se guardaba sin que nadie lo leyera.
 */
class ConfiguracionQueSeCumpleTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioCon(string ...$claves): User
    {
        $rol = Role::create(['nombre' => 'Rol '.uniqid()]);
        $rol->sincronizarPermisos(
            collect($claves)->map(fn ($c) => Permission::firstOrCreate(['clave' => $c])->id)->all()
        );

        return User::factory()->create(['role_id' => $rol->id]);
    }

    private function vender(User $usuario, array $extra = []): TestResponse
    {
        $producto = Producto::factory()->create(['stock' => 50]);

        return $this->actingAs($usuario)->post(route('ventas.store'), array_merge([
            'cliente_id' => Cliente::factory()->create()->id,
            'fecha'      => now()->toDateString(),
            'estado'     => 'completada',
            'detalles'   => [['producto_id' => $producto->id, 'cantidad' => 1, 'precio' => 1000]],
        ], $extra));
    }

    public function test_apagar_los_descuentos_se_guarda_y_se_respeta(): void
    {
        $admin = $this->usuarioCon('configuracion.ver', 'configuracion.editar', 'ventas.ver', 'ventas.crear');
        Setting::establecer('ventas_permite_descuento', '1', 'ventas');

        // La casilla destildada no manda el campo.
        $this->actingAs($admin)->post(route('configuracion.update'), ['sistema_iva' => '21'])
            ->assertSessionHasNoErrors();
        $this->assertSame('0', Setting::obtener('ventas_permite_descuento'));

        $this->vender($admin, ['descuento_tipo' => 'porcentaje', 'descuento' => 5])
            ->assertSessionHasErrors('descuento');
        $this->assertSame(0, Venta::count());

        // Sin descuento sigue vendiendo.
        $this->vender($admin)->assertSessionHasNoErrors();
        $this->assertSame(1, Venta::count());
    }

    public function test_por_encima_del_limite_solo_descuenta_quien_tiene_permiso(): void
    {
        Setting::establecer('ventas_limite_descuento', '10', 'ventas');
        $vendedor = $this->usuarioCon('ventas.ver', 'ventas.crear');
        $jefe = $this->usuarioCon('ventas.ver', 'ventas.crear', 'ventas.descuento_libre');

        $this->vender($vendedor, ['descuento_tipo' => 'porcentaje', 'descuento' => 10])
            ->assertSessionHasNoErrors();
        $this->assertSame(1, Venta::count());

        $this->vender($vendedor, ['descuento_tipo' => 'porcentaje', 'descuento' => 11])
            ->assertSessionHasErrors('descuento');
        // El mismo descuento, pero en monto fijo: 150 sobre 1000 es 15%.
        $this->vender($vendedor, ['descuento_tipo' => 'monto', 'descuento' => 150])
            ->assertSessionHasErrors('descuento');
        $this->assertSame(1, Venta::count());

        $this->vender($jefe, ['descuento_tipo' => 'porcentaje', 'descuento' => 40])
            ->assertSessionHasNoErrors();
        $this->assertSame(2, Venta::count());
    }

    public function test_el_prefijo_se_guarda_sin_guion_y_el_numero_sale_bien(): void
    {
        $admin = $this->usuarioCon('configuracion.ver', 'configuracion.editar', 'ventas.ver', 'ventas.crear');

        $this->actingAs($admin)->post(route('configuracion.update'), [
            'ventas_prefijo_numero' => 'fac-',
        ])->assertSessionHasNoErrors();

        $this->assertSame('FAC', Setting::obtener('ventas_prefijo_numero'));

        $this->vender($admin);
        $this->assertSame('FAC-00001', Venta::firstOrFail()->numero);
    }

    public function test_la_configuracion_rechaza_valores_que_romperian_el_sistema(): void
    {
        $admin = $this->usuarioCon('configuracion.ver', 'configuracion.editar');

        $this->actingAs($admin)->post(route('configuracion.update'), [
            'sistema_iva'             => '250',
            'sistema_moneda'          => 'XXX',
            'empresa_logo'            => 'javascript:alert(1)',
            'empresa_email'           => 'no-es-un-correo',
            'ventas_prefijo_numero'   => 'A B',
            'ventas_cantidad_digitos' => '99',
            'ventas_limite_descuento' => '-3',
        ])->assertSessionHasErrors([
            'sistema_iva', 'sistema_moneda', 'empresa_logo', 'empresa_email',
            'ventas_prefijo_numero', 'ventas_cantidad_digitos', 'ventas_limite_descuento',
        ]);
    }

    public function test_el_iva_llega_al_formulario_como_numero_y_no_como_codigo(): void
    {
        $admin = $this->usuarioCon('ventas.ver', 'ventas.crear');
        Setting::establecer('sistema_iva', '1;alert(1)', 'sistema');

        $html = $this->actingAs($admin)->get(route('ventas.create'))->assertOk()->getContent();

        $this->assertStringNotContainsString('alert(1)', $html);
        $this->assertMatchesRegularExpression('/ivaPorcentaje = 1;/', $html);
    }

    public function test_las_devoluciones_numeran_con_el_numerador_central_y_siguen_la_serie(): void
    {
        $admin = User::factory()->create(['role_id' => $this->rolAdministrador()->id]);
        $this->vender($admin, ['detalles' => [[
            'producto_id' => Producto::factory()->create(['stock' => 20])->id, 'cantidad' => 5, 'precio' => 100,
        ]]]);
        $venta = Venta::firstOrFail();
        $producto = $venta->detalles()->firstOrFail()->producto_id;

        foreach ([1, 2] as $_) {
            $respuesta = $this->actingAs($admin)->post(route('devoluciones.venta.store', $venta), [
                'motivo'   => 'fallado',
                'detalles' => [['producto_id' => $producto, 'cantidad' => 1]],
            ]);
            $respuesta->assertRedirect(route('ventas.show', $venta));
        }

        $this->assertSame(['DEV-00001', 'DEV-00002'], Devolucion::orderBy('id')->pluck('numero')->all());
        $this->assertSame(2, (int) DB::table('contadores')->where('serie', 'devoluciones:DEV')->value('ultimo'));
    }
}
