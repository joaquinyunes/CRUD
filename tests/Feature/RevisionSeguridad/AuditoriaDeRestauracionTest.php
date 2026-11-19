<?php

namespace Tests\Feature\RevisionSeguridad;

use App\Models\Auditoria;
use App\Models\Permission;
use App\Models\Role;
use App\Services\BackupService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * M10: restaurar un respaldo no borra el rastro de auditoría (quedaría un camino para
 * limpiar lo que alguien hizo) y los cambios de permisos de un rol dejan huella.
 */
class AuditoriaDeRestauracionTest extends TestCase
{
    public function test_restaurar_conserva_la_auditoria_posterior_al_respaldo_y_se_audita_a_si_mismo(): void
    {
        Storage::fake('local');
        $servicio = app(BackupService::class);

        $nombre = $servicio->crear();
        $lineaPosterior = Auditoria::create([
            'user_id'   => null, 'ip' => 'x', 'accion' => 'created', 'modelo_afectado' => 'Cosa',
            'modelo_id' => 99, 'created_at' => now(),
        ]);

        $servicio->restaurar(Storage::disk('local')->path('backups/'.$nombre));

        $this->assertNotNull(Auditoria::find($lineaPosterior->id), 'Restaurar no puede borrar lo que pasó después del respaldo.');
        $this->assertSame(1, Auditoria::where('accion', 'restaurar_backup')->count());
    }

    public function test_cambiar_los_permisos_de_un_rol_deja_rastro_en_la_auditoria(): void
    {
        $rol = Role::create(['nombre' => 'Cajero']);
        $backup = Permission::firstOrCreate(['clave' => 'backup.gestionar']);

        // Un sync() directo NO deja rastro (por eso el sistema usa sincronizarPermisos).
        $rol->permissions()->sync([$backup->id]);
        $this->assertSame(0, Auditoria::where('accion', 'permisos_modificados')->count());

        $rol->sincronizarPermisos([]);
        $rol->sincronizarPermisos([$backup->id]);

        $huella = Auditoria::where('accion', 'permisos_modificados')->orderBy('id')->get();
        $this->assertCount(2, $huella);
        $this->assertSame(['permisos' => ['backup.gestionar']], $huella[0]->valor_anterior);
        $this->assertSame(['permisos' => ['backup.gestionar']], $huella[1]->valor_nuevo);
    }
}
