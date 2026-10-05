<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EncabezadosDeSeguridadTest extends TestCase
{
    use RefreshDatabase;

    public function test_las_respuestas_llevan_los_encabezados_de_seguridad(): void
    {
        $respuesta = $this->get('/login');

        $respuesta->assertHeader('X-Content-Type-Options', 'nosniff');
        $respuesta->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $respuesta->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $respuesta->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_tambien_las_respuestas_de_error_y_las_de_la_api(): void
    {
        $this->get('/no-existe')->assertNotFound()->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $this->getJson('/api/user')->assertUnauthorized()->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_hsts_solo_en_produccion_y_por_https(): void
    {
        $this->app['env'] = 'production';

        $this->get('https://localhost/login')->assertHeader('Strict-Transport-Security');
        $this->get('http://localhost/login')->assertHeaderMissing('Strict-Transport-Security');
    }
}
