<?php

namespace Tests\Feature\RevisionSeguridad;

use App\Models\Archivo;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Devolucion;
use App\Models\Permission;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Role;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * M2, M3 y M4: compras, devoluciones y archivos ajenos no se ven; reembolsos y
 * egresos grandes piden permiso.
 */
class VisibilidadYTopesTest extends TestCase
{
    private function usuarioCon(string ...$claves): User
    {
        $rol = Role::create(['nombre' => 'Rol '.uniqid()]);
        $rol->sincronizarPermisos(collect($claves)->map(fn ($c) => Permission::firstOrCreate(['clave' => $c])->id)->all());
        Role::olvidarClaves($rol->id);

        return User::factory()->create(['role_id' => $rol->id]);
    }

    private function comprar(User $u, Producto $p): Compra
    {
        $this->actingAs($u)->post(route('compras.store'), [
            'proveedor_id' => Proveedor::factory()->create()->id,
            'fecha'        => now()->toDateString(),
            'estado'       => 'completada',
            'detalles'     => [['producto_id' => $p->id, 'cantidad' => 10, 'precio' => 50]],
        ])->assertSessionHasNoErrors();

        return Compra::latest('id')->firstOrFail();
    }

    private function vender(User $u, Producto $p): Venta
    {
        $this->actingAs($u)->post(route('ventas.store'), [
            'cliente_id' => Cliente::factory()->create(['limite_credito' => 1000000])->id,
            'fecha'      => now()->toDateString(),
            'estado'     => 'completada',
            'detalles'   => [['producto_id' => $p->id, 'cantidad' => 4, 'precio' => 100]],
        ])->assertSessionHasNoErrors();

        return Venta::latest('id')->firstOrFail();
    }

    // ---- M2 -----------------------------------------------------------------

    public function test_las_compras_de_otro_no_se_ven_ni_se_anulan(): void
    {
        $producto = Producto::factory()->create(['stock' => 0]);
        $ana = $this->usuarioCon('compras.ver', 'compras.crear', 'compras.eliminar');
        $beto = $this->usuarioCon('compras.ver', 'compras.crear', 'compras.eliminar');
        $compra = $this->comprar($ana, $producto);

        $this->actingAs($beto)->get(route('compras.show', $compra))->assertForbidden();
        $this->actingAs($beto)->get(route('compras.index'))->assertOk()->assertDontSee($compra->numero);
        $this->actingAs($beto)->get(route('pdf.compra', $compra->id))->assertForbidden();
        $this->actingAs($beto, 'sanctum')->getJson("/api/compras/{$compra->id}")->assertForbidden();
        Auth::shouldUse('web');  // actingAs(..., 'sanctum') deja esa guarda por defecto
        $this->actingAs($beto, 'sanctum')->deleteJson("/api/compras/{$compra->id}")->assertForbidden();
        $this->actingAs($beto, 'sanctum')->getJson('/api/compras?buscar='.$compra->numero)->assertOk()->assertJsonCount(0, 'data');
        Auth::shouldUse('web');

        $this->actingAs($ana)->get(route('compras.show', $compra))->assertOk();
    }

    // ---- M3 -----------------------------------------------------------------

    public function test_las_devoluciones_de_otro_no_se_ven_ni_se_crean_sobre_ventas_ajenas(): void
    {
        $producto = Producto::factory()->create(['stock' => 50]);
        $ana = $this->usuarioCon('ventas.ver', 'ventas.crear', 'devoluciones.ver', 'devoluciones.crear');
        $beto = $this->usuarioCon('ventas.ver', 'ventas.crear', 'devoluciones.ver', 'devoluciones.crear');
        $supervisor = $this->usuarioCon('devoluciones.ver', 'ventas.gestionar_todas');
        $venta = $this->vender($ana, $producto);

        $this->actingAs($beto)->post(route('devoluciones.venta.store', $venta), [
            'motivo' => 'x', 'detalles' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ])->assertForbidden();

        $this->actingAs($ana)->post(route('devoluciones.venta.store', $venta), [
            'motivo' => 'x', 'detalles' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ])->assertSessionHasNoErrors();
        $devolucion = Devolucion::firstOrFail();

        $this->actingAs($beto)->get(route('devoluciones.show', $devolucion))->assertForbidden();
        $this->actingAs($beto)->get(route('devoluciones.index'))->assertOk()->assertDontSee($devolucion->numero);
        $this->actingAs($ana)->get(route('devoluciones.show', $devolucion))->assertOk();
        $this->actingAs($supervisor)->get(route('devoluciones.show', $devolucion))->assertOk();
    }

    public function test_los_archivos_de_otro_no_se_descargan_ni_se_borran(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $ana = $this->usuarioCon('archivos.ver', 'archivos.gestionar');
        $beto = $this->usuarioCon('archivos.ver', 'archivos.gestionar');
        $supervisor = $this->usuarioCon('archivos.ver', 'archivos.gestionar', 'archivos.gestionar_todas');

        $this->actingAs($ana)->post(route('archivos.store'), ['archivo' => UploadedFile::fake()->image('foto.png')])->assertSessionHasNoErrors();
        $archivo = Archivo::firstOrFail();

        $this->actingAs($beto)->get(route('archivos.download', $archivo))->assertForbidden();
        $this->actingAs($beto)->get(route('archivos.show', $archivo))->assertForbidden();
        $this->actingAs($beto)->delete(route('archivos.destroy', $archivo))->assertForbidden();
        $this->actingAs($beto)->get(route('archivos.index'))->assertOk()->assertDontSee('foto.png');
        $this->assertSame(1, Archivo::count());

        $this->actingAs($ana)->get(route('archivos.download', $archivo))->assertOk();
        $this->actingAs($supervisor)->get(route('archivos.index'))->assertOk()->assertSee('foto.png');
    }

    // ---- M4 -----------------------------------------------------------------

    public function test_un_reembolso_en_efectivo_pide_permiso(): void
    {
        $producto = Producto::factory()->create(['stock' => 50]);
        $sin = $this->usuarioCon('ventas.ver', 'ventas.crear', 'devoluciones.crear', 'caja.operar', 'caja.ver');
        $con = $this->usuarioCon('ventas.ver', 'ventas.crear', 'devoluciones.crear', 'devoluciones.reembolsar', 'caja.operar', 'caja.ver');
        $this->actingAs($sin)->post(route('caja.abrir'), ['monto_inicial' => 1000]);
        $this->actingAs($con)->post(route('caja.abrir'), ['monto_inicial' => 1000]);
        $ventaSin = $this->vender($sin, $producto);
        $ventaCon = $this->vender($con, $producto);

        $datos = fn (Venta $v) => ['motivo' => 'x', 'reembolso_efectivo' => 1, 'detalles' => [['producto_id' => $producto->id, 'cantidad' => 1]]];

        $this->actingAs($sin)->post(route('devoluciones.venta.store', $ventaSin), $datos($ventaSin))->assertSessionHasErrors('reembolso_efectivo');
        $this->assertSame(0, Devolucion::count());

        $this->actingAs($con)->post(route('devoluciones.venta.store', $ventaCon), $datos($ventaCon))->assertSessionHasNoErrors();
        $this->assertSame(1, Devolucion::count());
    }

    public function test_un_egreso_manual_grande_pide_autorizacion(): void
    {
        $operador = $this->usuarioCon('caja.ver', 'caja.operar');
        $supervisor = $this->usuarioCon('caja.ver', 'caja.operar', 'caja.egreso_libre');
        $this->actingAs($operador)->post(route('caja.abrir'), ['monto_inicial' => 100000]);
        $this->actingAs($supervisor)->post(route('caja.abrir'), ['monto_inicial' => 100000]);

        $this->actingAs($operador)->post(route('caja.movimiento'), ['tipo' => 'egreso', 'concepto' => 'Chico', 'monto' => 500])->assertSessionHasNoErrors();
        $this->actingAs($operador)->post(route('caja.movimiento'), ['tipo' => 'egreso', 'concepto' => 'Grande', 'monto' => 50000])->assertSessionHasErrors('monto');
        $this->actingAs($operador)->post(route('caja.movimiento'), ['tipo' => 'ingreso', 'concepto' => 'Ingreso', 'monto' => 50000])->assertSessionHasNoErrors();
        $this->actingAs($supervisor)->post(route('caja.movimiento'), ['tipo' => 'egreso', 'concepto' => 'Grande', 'monto' => 50000])->assertSessionHasNoErrors();
    }
}
