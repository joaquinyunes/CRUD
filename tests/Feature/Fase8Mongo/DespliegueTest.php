<?php

namespace Tests\Feature\Fase8Mongo;

use App\Support\Mongo\Indices;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Fase 8: lo que el despliegue necesita comprobar desde el repositorio.
 */
class DespliegueTest extends TestCase
{
    public function test_el_health_check_responde_200_con_mongodb_arriba(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_el_health_check_falla_si_mongodb_no_responde(): void
    {
        config(['database.connections.mongodb.dsn' => 'mongodb://127.0.0.1:1/?serverSelectionTimeoutMS=300&connectTimeoutMS=300']);
        DB::purge('mongodb');

        try {
            $this->get('/up')->assertStatus(500);
        } finally {
            config(['database.connections.mongodb.dsn' => env('MONGODB_URI', 'mongodb://127.0.0.1:27017/?replicaSet=rs0')]);
            DB::purge('mongodb');
        }
    }

    public function test_crear_los_indices_en_una_base_vacia_deja_la_instalacion_lista(): void
    {
        DB::connection('mongodb')->getDatabase()->drop();

        $this->artisan('mongo:indices')->assertSuccessful();

        $this->assertGreaterThan(40, Indices::crear());
        $nombres = collect(DB::connection('mongodb')->getCollection('ventas')->listIndexes())->map->getName()->all();
        $this->assertContains('numero_unico', $nombres);
    }

    public function test_los_archivos_de_despliegue_apuntan_a_mongodb_y_no_a_sql(): void
    {
        $raiz = base_path();
        $dockerfile = file_get_contents("$raiz/Dockerfile");
        $render = file_get_contents("$raiz/render.yaml");
        $entrypoint = file_get_contents("$raiz/docker/entrypoint.sh");
        $compose = file_get_contents("$raiz/docker-compose.yml");

        $this->assertStringContainsString('mongodb', $dockerfile);
        $this->assertStringNotContainsString('pdo_pgsql', $dockerfile);
        $this->assertStringNotContainsString('pdo_mysql', $dockerfile);

        $this->assertStringContainsString('DB_CONNECTION', $render);
        $this->assertMatchesRegularExpression('/key: DB_CONNECTION\s+value: mongodb/', $render);
        $this->assertMatchesRegularExpression('/key: MONGODB_URI\s+sync: false/', $render, 'La cadena de Atlas se carga a mano, nunca en el repositorio.');
        $this->assertStringNotContainsString('databases:', $render);
        $this->assertStringContainsString('healthCheckPath: /up', $render);

        $this->assertStringNotContainsString('artisan migrate', $entrypoint);
        $this->assertStringContainsString('mongo:indices', $entrypoint);

        $this->assertStringContainsString('mongo:7', $compose);
        $this->assertStringNotContainsString('postgres', $compose);
    }

    public function test_no_hay_secretos_ni_cadenas_de_conexion_reales_en_los_archivos_de_despliegue(): void
    {
        foreach (['render.yaml', 'docker-compose.yml', '.env.example'] as $archivo) {
            $texto = file_get_contents(base_path($archivo));

            $this->assertDoesNotMatchRegularExpression('/mongodb\+srv:\/\/[^\s"\'<>]+:[^\s"\'<>]+@/', $texto, "$archivo contiene una cadena de Atlas con usuario y clave");
            $this->assertDoesNotMatchRegularExpression('/APP_KEY=base64:[A-Za-z0-9+\/=]{20,}/', $texto, "$archivo contiene una APP_KEY");
        }
    }
}
