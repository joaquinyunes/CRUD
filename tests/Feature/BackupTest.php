<?php

namespace Tests\Feature;

use App\Exceptions\ReglaDeNegocioException;
use App\Models\Cliente;
use App\Models\Permission;
use App\Models\Producto;
use App\Models\Role;
use App\Models\User;
use App\Models\Venta;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role_id' => $this->rolAdministrador()->id]);
    }

    private function conVenta(User $admin): Venta
    {
        $producto = Producto::factory()->create(['stock' => 10, 'nombre' => 'Original']);
        $this->actingAs($admin)->post(route('ventas.store'), [
            'cliente_id' => Cliente::factory()->create()->id,
            'fecha'      => now()->toDateString(),
            'estado'     => 'completada',
            'detalles'   => [['producto_id' => $producto->id, 'cantidad' => 2, 'precio' => 100]],
        ])->assertSessionHasNoErrors();

        return Venta::firstOrFail();
    }

    public function test_un_backup_se_restaura_con_los_mismos_datos_y_ids(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $venta = $this->conVenta($admin);
        $servicio = app(BackupService::class);

        $nombre = $servicio->crear();
        $this->assertTrue($servicio->existe($nombre));

        // Después del respaldo: se borra todo lo de ventas y se inventa otra cosa.
        DB::table('ventas_detalle')->delete();
        DB::table('ventas')->delete();
        Producto::query()->update(['nombre' => 'Cambiado', 'stock' => 999]);
        Producto::factory()->create(['nombre' => 'Posterior']);

        $servicio->restaurar(Storage::disk('local')->path('backups/'.$nombre));

        $this->assertSame($venta->numero, Venta::firstOrFail()->numero);
        $this->assertSame(1, Venta::count());
        $this->assertSame(1, DB::table('ventas_detalle')->count());
        $this->assertSame(['Original'], Producto::pluck('nombre')->all());
        $this->assertSame(8, Producto::firstOrFail()->stock);
        $this->assertNotNull(User::find($admin->id));
        $this->assertSame(1, (int) DB::table('contadores')->where('serie', 'like', 'ventas:%')->value('ultimo'));
    }

    public function test_un_archivo_que_no_es_un_backup_no_toca_la_base(): void
    {
        $this->conVenta($this->admin());

        $falso = tempnam(sys_get_temp_dir(), 'x');
        file_put_contents($falso, "esto no es un respaldo\n");

        try {
            app(BackupService::class)->restaurar($falso);
            $this->fail('Debió rechazar el archivo.');
        } catch (ReglaDeNegocioException $e) {
            $this->assertStringContainsString('no es un respaldo', $e->getMessage());
        }

        $this->assertSame(1, Venta::count());
    }

    public function test_un_backup_danado_a_mitad_deja_la_base_como_estaba(): void
    {
        Storage::fake('local');
        $this->conVenta($this->admin());
        $nombre = app(BackupService::class)->crear();

        // Cabecera válida, luego una fila de una tabla que no existe.
        $original = gzdecode(Storage::disk('local')->get('backups/'.$nombre));
        $corrupto = tempnam(sys_get_temp_dir(), 'x');
        file_put_contents($corrupto, gzencode($original.json_encode(['t' => 'tabla_inventada', 'f' => ['a' => 1]])."\n"));

        try {
            app(BackupService::class)->restaurar($corrupto);
            $this->fail('Debió rechazar el archivo dañado.');
        } catch (ReglaDeNegocioException) {
            $this->assertSame(1, Venta::count());
            $this->assertSame(1, Producto::count());
        }
    }

    public function test_no_restaura_un_backup_de_otra_estructura(): void
    {
        Storage::fake('local');
        $nombre = app(BackupService::class)->crear();

        $lineas = explode("\n", gzdecode(Storage::disk('local')->get('backups/'.$nombre)));
        $cabecera = json_decode($lineas[0], true);
        $cabecera['migraciones'][] = '2099_01_01_000000_del_futuro';
        $lineas[0] = json_encode($cabecera);

        $otro = tempnam(sys_get_temp_dir(), 'x');
        file_put_contents($otro, gzencode(implode("\n", $lineas)));

        $this->expectExceptionMessage('otra versión de la estructura');
        app(BackupService::class)->restaurar($otro);
    }

    public function test_la_pantalla_crea_lista_descarga_y_elimina(): void
    {
        Storage::fake('local');
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('backup.crear'))->assertRedirect(route('backup.index'));
        $nombre = app(BackupService::class)->listar()[0]['nombre'];

        $this->actingAs($admin)->get(route('backup.index'))->assertOk()->assertSee($nombre);
        $this->actingAs($admin)->get(route('backup.descargar', $nombre))->assertOk();

        $this->actingAs($admin)->delete(route('backup.eliminar', $nombre))->assertRedirect();
        $this->assertSame([], app(BackupService::class)->listar());
    }

    public function test_restaurar_desde_la_pantalla(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $this->conVenta($admin);
        $nombre = app(BackupService::class)->crear();
        DB::table('ventas_detalle')->delete();
        DB::table('ventas')->delete();

        $archivo = UploadedFile::fake()->createWithContent('b.json.gz', Storage::disk('local')->get('backups/'.$nombre));
        $this->actingAs($admin)->post(route('backup.restaurar'), ['backup_file' => $archivo])
            ->assertRedirect(route('backup.index'))
            ->assertSessionHas('success');

        $this->assertSame(1, Venta::count());
    }

    public function test_nombres_que_no_son_de_un_backup_no_se_sirven_ni_se_borran(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('backups/secreto.txt', 'x');
        $admin = $this->admin();

        $this->actingAs($admin)->get('/backup/descargar/secreto.txt')->assertNotFound();
        $this->actingAs($admin)->get('/backup/descargar/..%2F..%2F.env')->assertNotFound();
        $this->actingAs($admin)->delete('/backup/eliminar/secreto.txt')->assertNotFound();
        Storage::disk('local')->assertExists('backups/secreto.txt');
    }

    public function test_hace_falta_el_permiso_de_backup(): void
    {
        $rol = Role::create(['nombre' => 'Otro']);
        $rol->sincronizarPermisos([Permission::firstOrCreate(['clave' => 'ventas.ver'])->id]);
        $usuario = User::factory()->create(['role_id' => $rol->id]);

        $this->actingAs($usuario)->get(route('backup.index'))->assertForbidden();
        $this->actingAs($usuario)->post(route('backup.crear'))->assertForbidden();
    }

    public function test_el_comando_conserva_solo_los_mas_recientes(): void
    {
        Storage::fake('local');

        foreach (['2026-01-01_010000', '2026-01-02_010000', '2026-01-03_010000'] as $i => $fecha) {
            Storage::disk('local')->put("backups/backup_{$fecha}.json.gz", 'x');
            touch(Storage::disk('local')->path("backups/backup_{$fecha}.json.gz"), 1_700_000_000 + $i * 1000);
        }

        $this->artisan('backup:crear --conservar=2')->assertSuccessful();

        $this->assertCount(2, app(BackupService::class)->listar());
    }
}
