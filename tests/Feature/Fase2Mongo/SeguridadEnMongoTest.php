<?php

namespace Tests\Feature\Fase2Mongo;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Fase 2: lo que cambia al guardar usuarios, roles, permisos y tokens de API en
 * MongoDB (sin tablas intermedias ni claves foráneas).
 */
class SeguridadEnMongoTest extends TestCase
{
    // ---- Sanctum con los tokens en MongoDB ----

    public function test_el_token_de_api_se_guarda_en_mongo_y_sirve_para_entrar_y_salir(): void
    {
        $usuario = User::factory()->create(['role_id' => $this->rolAdministrador()->id]);

        $token = $this->postJson('/api/login', ['email' => $usuario->email, 'password' => 'password'])
            ->assertOk()
            ->json('token');

        $crudo = DB::connection('mongodb')->getCollection('personal_access_tokens')->findOne();
        $this->assertSame($usuario->id, $crudo['tokenable_id']);
        $this->assertSame(User::class, $crudo['tokenable_type']);

        $this->withToken($token)->getJson('/api/user')->assertOk()->assertJsonPath('email', $usuario->email);

        $this->withToken($token)->postJson('/api/logout')->assertOk();
        $this->assertSame(0, DB::connection('mongodb')->table('personal_access_tokens')->count());

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/user')->assertUnauthorized();
    }

    public function test_desactivar_a_un_usuario_le_borra_los_tokens(): void
    {
        $usuario = User::factory()->create();
        $usuario->createToken('a');
        $usuario->createToken('b');
        $this->assertSame(2, $usuario->tokens()->count());

        $usuario->tokens()->delete();

        $this->assertSame(0, DB::connection('mongodb')->table('personal_access_tokens')->count());
    }

    // ---- Roles y permisos: muchos a muchos sin tabla intermedia ----

    public function test_sync_acepta_lista_collection_o_id_suelto_y_guarda_ids_en_ambos_lados(): void
    {
        $rol = Role::create(['nombre' => 'Caja']);
        $ver = Permission::create(['clave' => 'ventas.ver']);
        $crear = Permission::create(['clave' => 'ventas.crear']);
        $borrar = Permission::create(['clave' => 'ventas.eliminar']);

        $rol->permissions()->sync([$ver->id, $crear->id]);
        $this->assertSame([$ver->id, $crear->id], $rol->fresh()->permission_ids);
        $this->assertSame([$rol->id], $ver->fresh()->role_ids);

        $rol->permissions()->sync(Permission::whereIn('clave', ['ventas.crear', 'ventas.eliminar'])->pluck('id'));
        $this->assertEqualsCanonicalizing(['ventas.crear', 'ventas.eliminar'], $rol->fresh()->permissions()->pluck('clave')->all());
        $this->assertEmpty($ver->fresh()->role_ids ?? []);

        $rol->permissions()->syncWithoutDetaching($ver->id);
        $this->assertEqualsCanonicalizing(['ventas.ver', 'ventas.crear', 'ventas.eliminar'], $rol->fresh()->permissions()->pluck('clave')->all());

        $rol->permissions()->detach();
        $this->assertEmpty($rol->fresh()->permission_ids ?? []);
        $this->assertEmpty($borrar->fresh()->role_ids ?? []);
    }

    public function test_los_permisos_de_un_rol_se_leen_por_clave(): void
    {
        $rol = Role::create(['nombre' => 'Lector']);
        $rol->sincronizarPermisos([Permission::create(['clave' => 'productos.ver'])->id]);

        $this->assertTrue($rol->fresh()->tienePermiso('productos.ver'));
        $this->assertFalse($rol->fresh()->tienePermiso('productos.crear'));
    }

    public function test_el_comodin_hace_administrador_a_cualquier_rol_y_protege_al_ultimo(): void
    {
        $dueno = $this->rolAdministrador('Dueño');
        $empleado = Role::create(['nombre' => 'Empleado']);

        $unico = User::factory()->create(['role_id' => $dueno->id]);
        $otro = User::factory()->create(['role_id' => $empleado->id]);

        $this->assertSame([$dueno->id], Role::idsAdministradores());
        $this->assertEquals([$unico->id], User::administradores()->get()->modelKeys());
        $this->assertTrue($unico->dejariaSinAdministrador(null));
        $this->assertTrue($unico->dejariaSinAdministrador($empleado->id));
        $this->assertFalse($otro->dejariaSinAdministrador(null));

        $segundo = User::factory()->create(['role_id' => $dueno->id]);
        $this->assertFalse($unico->dejariaSinAdministrador(null));

        $segundo->update(['activo' => false]);
        $this->assertTrue($unico->dejariaSinAdministrador(null));
    }

    public function test_el_correo_de_un_usuario_es_unico_en_la_base(): void
    {
        User::factory()->create(['email' => 'a@b.com']);

        $this->expectException(\Throwable::class);
        $this->expectExceptionMessageMatches('/E11000|duplicate key/i');
        User::factory()->create(['email' => 'a@b.com']);
    }

    // ---- exists:/unique: con ids numéricos ----

    public function test_las_reglas_exists_y_unique_comparan_ids_numericos_que_llegan_como_texto(): void
    {
        $rol = Role::create(['nombre' => 'X']);

        $valida = fn (array $datos, array $reglas) => validator($datos, $reglas)->passes();

        $this->assertTrue($valida(['r' => (string) $rol->id], ['r' => 'exists:mongodb.roles,id']));
        $this->assertFalse($valida(['r' => '9999'], ['r' => 'exists:mongodb.roles,id']));
        $this->assertFalse($valida(['n' => 'X'], ['n' => 'unique:mongodb.roles,nombre']));
        $this->assertTrue($valida(['n' => 'X'], ['n' => 'unique:mongodb.roles,nombre,'.$rol->id.',id']));
    }
}
