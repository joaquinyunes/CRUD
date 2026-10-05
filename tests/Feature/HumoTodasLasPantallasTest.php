<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RutaLaravel;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Entra a cada pantalla de lectura del sistema, con los datos de la demo
 * cargados y el usuario administrador, y exige que ninguna devuelva un error.
 *
 * Es la red de seguridad contra lo que ya pasó: pantallas que renderizaban en
 * blanco o reventaban con SQL de un solo motor y que ningún test miraba.
 */
class HumoTodasLasPantallasTest extends TestCase
{
    use RefreshDatabase;

    public function test_ninguna_pantalla_de_lectura_devuelve_error_con_datos_reales(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DemoSeeder::class);

        $admin = User::where('email', 'admin@admin.com')->firstOrFail();
        $this->actingAs($admin);

        $fallas = [];
        $visitadas = 0;

        foreach (Route::getRoutes() as $ruta) {
            /** @var RutaLaravel $ruta */
            if (! in_array('GET', $ruta->methods(), true) || ! $this->esDeLaAplicacion($ruta)) {
                continue;
            }

            $url = $this->urlConEjemplos($ruta);
            if ($url === null) {
                continue;
            }

            $respuesta = $this->get($url);
            $visitadas++;

            if ($respuesta->getStatusCode() >= 500) {
                $fallas[] = "{$respuesta->getStatusCode()} {$url}";
            }
        }

        $this->assertGreaterThan(60, $visitadas, 'El recorrido debería cubrir las pantallas principales.');
        $this->assertSame([], $fallas, "Pantallas que fallan:\n".implode("\n", $fallas));
    }

    private function esDeLaAplicacion(RutaLaravel $ruta): bool
    {
        $uri = $ruta->uri();

        foreach (['api/', 'up', 'storage', 'sanctum', 'logout', 'backup/descargar'] as $excluida) {
            if (str_starts_with($uri, $excluida)) {
                return false;
            }
        }

        return ! str_contains($uri, 'verify-email') && ! str_contains($uri, 'reset-password')
            && ! str_contains($uri, 'doble-factor') && $uri !== 'confirm-password';
    }

    /** Reemplaza {parametro} por el id 1: la demo trae registros de cada tipo. */
    private function urlConEjemplos(RutaLaravel $ruta): ?string
    {
        $uri = preg_replace('/\{[^}]+\}/', '1', $ruta->uri());

        return '/'.ltrim($uri, '/');
    }
}
