<?php

namespace Tests\Feature;

use App\Models\Archivo;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Evento;
use App\Models\Permission;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Role;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Pantallas que solo pedían haber iniciado sesión. El menú ya las ocultaba a
 * quien no tenía el permiso, pero escribir la URL a mano funcionaba igual.
 */
class AccesoPorPermisoTest extends TestCase
{
    use RefreshDatabase;

    /** Un usuario con exactamente esos permisos, ni uno más. */
    private function usuarioCon(string ...$claves): User
    {
        $rol = Role::create(['nombre' => 'Rol '.uniqid()]);
        $rol->sincronizarPermisos(
            collect($claves)->map(fn ($c) => Permission::firstOrCreate(['clave' => $c])->id)->all()
        );

        return User::factory()->create(['role_id' => $rol->id]);
    }

    private function venta(): Venta
    {
        $admin = User::factory()->create(['role_id' => $this->rolAdministrador()->id]);
        $producto = Producto::factory()->create(['stock' => 10]);
        $this->actingAs($admin)->post(route('ventas.store'), [
            'cliente_id' => Cliente::factory()->create()->id,
            'fecha'      => now()->toDateString(),
            'estado'     => 'completada',
            'detalles'   => [['producto_id' => $producto->id, 'cantidad' => 1, 'precio' => 100]],
        ]);
        auth()->logout();

        return Venta::firstOrFail();
    }

    private function compra(): Compra
    {
        $admin = User::factory()->create(['role_id' => $this->rolAdministrador()->id]);
        $producto = Producto::factory()->create(['stock' => 10]);
        $this->actingAs($admin)->post(route('compras.store'), [
            'proveedor_id' => Proveedor::factory()->create()->id,
            'fecha'        => now()->toDateString(),
            'estado'       => 'completada',
            'detalles'     => [['producto_id' => $producto->id, 'cantidad' => 1, 'precio' => 50]],
        ]);
        auth()->logout();

        return Compra::firstOrFail();
    }

    public function test_los_pdf_piden_el_permiso_del_modulo(): void
    {
        $venta = $this->venta();
        $compra = $this->compra();
        $sinPermisos = $this->usuarioCon('dashboard.ver');

        foreach ([
            route('pdf.venta', $venta->id),
            route('pdf.boleta', $venta->id),
            route('pdf.compra', $compra->id),
            route('pdf.catalogo'),
            route('pdf.stock'),
        ] as $url) {
            $this->actingAs($sinPermisos)->get($url)->assertForbidden();
        }

        $this->actingAs($this->usuarioCon('ventas.ver'))->get(route('pdf.venta', $venta->id))->assertOk();
        $this->actingAs($this->usuarioCon('ventas.ver'))->get(route('pdf.compra', $compra->id))->assertForbidden();
        $this->actingAs($this->usuarioCon('compras.ver'))->get(route('pdf.compra', $compra->id))->assertOk();
    }

    public function test_archivos_tareas_calendario_y_configuracion_piden_su_permiso(): void
    {
        $sinPermisos = $this->usuarioCon('dashboard.ver');

        foreach ([
            route('archivos.index'),
            route('tareas.index'),
            route('calendario.index'),
            route('calendario.eventos-json'),
            route('configuracion.index'),
        ] as $url) {
            $this->actingAs($sinPermisos)->get($url)->assertForbidden();
        }

        $this->actingAs($this->usuarioCon('archivos.ver'))->get(route('archivos.index'))->assertOk();
        $this->actingAs($this->usuarioCon('tareas.ver'))->get(route('tareas.index'))->assertOk();
        $this->actingAs($this->usuarioCon('calendario.ver'))->get(route('calendario.index'))->assertOk();
        $this->actingAs($this->usuarioCon('configuracion.ver'))->get(route('configuracion.index'))->assertOk();
    }

    public function test_el_tablero_kanban_ordena_en_cualquier_motor(): void
    {
        // Ordenaba con FIELD(), que solo existe en MySQL.
        $this->actingAs($this->usuarioCon('tareas.ver'))
            ->get(route('tareas.index', ['vista' => 'kanban']))
            ->assertOk();
    }

    public function test_el_panel_de_indicadores_es_de_quien_tiene_el_permiso(): void
    {
        Venta::query()->delete();

        $this->actingAs($this->usuarioCon('productos.ver'))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('no incluye el panel');

        $this->actingAs($this->usuarioCon('dashboard.ver'))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('no incluye el panel');
    }

    public function test_un_rol_con_gestionar_todas_actua_sobre_comprobantes_ajenos(): void
    {
        $venta = $this->venta();

        $comun = $this->usuarioCon('ventas.ver', 'ventas.editar');
        $this->actingAs($comun)->get(route('ventas.edit', $venta))->assertForbidden();

        // No se llama Supervisor ni Administrador: lo que cuenta es el permiso.
        $jefe = $this->usuarioCon('ventas.ver', 'ventas.editar', 'ventas.gestionar_todas');
        $this->actingAs($jefe)->get(route('ventas.edit', $venta))->assertOk();
    }

    public function test_un_rol_llamado_supervisor_sin_el_permiso_no_actua_sobre_ajenos(): void
    {
        $venta = $this->venta();
        $rol = Role::create(['nombre' => Role::SUPERVISOR]);
        $rol->sincronizarPermisos([
            Permission::firstOrCreate(['clave' => 'ventas.ver'])->id,
            Permission::firstOrCreate(['clave' => 'ventas.editar'])->id,
        ]);

        $this->actingAs(User::factory()->create(['role_id' => $rol->id]))
            ->get(route('ventas.edit', $venta))
            ->assertForbidden();
    }

    public function test_los_archivos_nuevos_van_al_disco_privado_y_se_sirven_con_permiso(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $usuario = $this->usuarioCon('archivos.ver', 'archivos.gestionar');

        $this->actingAs($usuario)->post(route('archivos.store'), [
            'archivo' => UploadedFile::fake()->image('foto.png'),
        ])->assertRedirect();

        $archivo = Archivo::firstOrFail();
        Storage::disk('local')->assertExists($archivo->ruta);
        Storage::disk('public')->assertMissing($archivo->ruta);

        $this->actingAs($usuario)->get(route('archivos.show', $archivo))->assertOk();
        $this->actingAs($usuario)->get(route('archivos.download', $archivo))->assertOk();

        $this->actingAs($this->usuarioCon('dashboard.ver'))->get(route('archivos.show', $archivo))->assertForbidden();
        $this->actingAs($this->usuarioCon('dashboard.ver'))->get(route('archivos.download', $archivo))->assertForbidden();
    }

    public function test_un_archivo_subido_antes_del_disco_privado_se_sigue_encontrando(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Storage::disk('public')->put('archivos/viejo.pdf', '%PDF-1.4');
        $archivo = Archivo::create([
            'nombre' => 'viejo.pdf', 'ruta' => 'archivos/viejo.pdf', 'tipo' => 'application/pdf',
            'tamano' => 8, 'user_id' => User::factory()->create()->id,
        ]);
        $usuario = $this->usuarioCon('archivos.ver', 'archivos.gestionar');

        $this->actingAs($usuario)->get(route('archivos.download', $archivo))->assertOk();

        $this->actingAs($usuario)->delete(route('archivos.destroy', $archivo))->assertRedirect();
        Storage::disk('public')->assertMissing('archivos/viejo.pdf');
    }

    public function test_arrastrar_un_evento_valida_las_fechas(): void
    {
        $usuario = $this->usuarioCon('calendario.ver', 'calendario.gestionar');
        $evento = Evento::create([
            'titulo'  => 'Reunión', 'inicio' => now(), 'fin' => now()->addHour(),
            'user_id' => $usuario->id, 'todo_el_dia' => false, 'color' => '#336699',
        ]);

        $this->actingAs($usuario)->patchJson(route('calendario.mover', $evento), [])
            ->assertUnprocessable();

        $this->actingAs($usuario)->patchJson(route('calendario.mover', $evento), [
            'start' => '2026-11-02 10:00:00', 'end' => '2026-11-02 09:00:00',
        ])->assertUnprocessable();

        $this->actingAs($usuario)->patchJson(route('calendario.mover', $evento), [
            'start' => '2026-11-02 10:00:00', 'end' => '2026-11-02 11:00:00',
        ])->assertOk();
    }

    public function test_el_color_de_un_evento_tiene_que_ser_hexadecimal(): void
    {
        $usuario = $this->usuarioCon('calendario.ver', 'calendario.gestionar');

        $this->actingAs($usuario)->post(route('calendario.store'), [
            'titulo' => 'x', 'inicio' => now()->toDateTimeString(), 'color' => 'red;"onload=',
        ])->assertSessionHasErrors('color');
    }

    public function test_actualizar_una_instalacion_existente_no_le_quita_nada_al_supervisor(): void
    {
        // Una base anterior al permiso: el rol Supervisor existe y no lo tiene.
        $supervisor = Role::create(['nombre' => Role::SUPERVISOR]);
        $usuario = User::factory()->create(['role_id' => $supervisor->id]);
        $this->assertFalse($supervisor->tienePermiso('ventas.gestionar_todas'));

        (require database_path('migrations/2026_10_06_100000_permiso_gestionar_todas.php'))->up();

        $this->assertTrue($supervisor->fresh()->tienePermiso('ventas.gestionar_todas'));
        $this->assertTrue($supervisor->fresh()->tienePermiso('compras.gestionar_todas'));
        $this->assertTrue($supervisor->fresh()->tienePermiso('ventas.descuento_libre'));
        $this->assertNotNull($usuario->fresh()->role);
    }
}
