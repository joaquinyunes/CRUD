<?php

namespace Tests\Feature\RevisionSeguridad;

use App\Models\Deposito;
use App\Models\MovimientoStock;
use App\Models\Producto;
use App\Models\User;
use App\Support\ConfiguracionSegura;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * B1 (operadores de MongoDB en la entrada), B2 (importar stock como ajuste),
 * B4 (configuración de producción) y B5 (dompdf sin acceso remoto).
 */
class BajasTest extends TestCase
{
    private function admin(): User
    {
        return User::factory()->create(['role_id' => $this->rolAdministrador()->id]);
    }

    // ---- B1 -----------------------------------------------------------------

    public function test_los_operadores_de_mongo_en_la_query_se_rechazan(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/productos?categoria_id[$ne]=0')->assertStatus(400);
        $this->actingAs($admin)->get('/productos?buscar[$regex]=.*')->assertStatus(400);
        $this->actingAs($admin)->get('/productos?categoria_id=1')->assertOk();
    }

    public function test_los_operadores_de_mongo_en_el_cuerpo_json_y_en_formularios_se_rechazan(): void
    {
        $this->postJson('/api/login', ['email' => ['$gt' => ''], 'password' => 'x'])->assertStatus(400);
        $this->post('/login', ['email' => ['$ne' => null], 'password' => ['$ne' => null]])->assertStatus(400);
        $this->postJson('/api/login', ['email' => 'a@b.com', 'password' => 'x'])->assertStatus(422);
    }

    // ---- B2 -----------------------------------------------------------------

    private function importar(string $csv)
    {
        return $this->actingAs($this->admin())->post(route('importar.importar'), [
            'tipo'    => 'productos',
            'archivo' => UploadedFile::fake()->createWithContent('datos.csv', $csv),
        ]);
    }

    public function test_importar_stock_es_un_ajuste_con_movimiento_y_deposito(): void
    {
        $existente = Producto::factory()->create(['codigo' => 'EX-1', 'stock' => 5]);
        $existente->stockEn(Deposito::principalId());

        $this->importar("nombre,codigo,stock\nExistente,EX-1,12\nNuevo,NU-1,7\n")->assertSessionHas('success');

        $existente = Producto::where('codigo', 'EX-1')->firstOrFail();
        $nuevo = Producto::where('codigo', 'NU-1')->firstOrFail();

        $this->assertSame(12, $existente->stock);
        $this->assertSame(7, $nuevo->stock);
        // Quedó en el depósito (no solo en el campo del producto) y dejó movimiento.
        $this->assertSame(12, $existente->stockEn(Deposito::principalId()));
        $this->assertSame(7, $nuevo->stockEn(Deposito::principalId()));
        $this->assertGreaterThanOrEqual(2, MovimientoStock::count(), 'El stock importado no dejó rastro en los movimientos.');
    }

    public function test_un_stock_negativo_en_el_csv_se_rechaza_fila_por_fila(): void
    {
        $this->importar("nombre,codigo,stock\nMalo,MA-1,-3\nBueno,BU-1,4\n")->assertSessionHas('errores');

        $this->assertNull(Producto::where('codigo', 'MA-1')->first());
        $this->assertSame(4, Producto::where('codigo', 'BU-1')->value('stock'));
    }

    // ---- B4 -----------------------------------------------------------------

    public function test_el_disco_publico_es_un_error_en_produccion_y_el_mailer_log_un_aviso(): void
    {
        $this->assertNotEmpty(ConfiguracionSegura::errores(true, 'public'));
        $this->assertSame([], ConfiguracionSegura::errores(true, 'local'));
        $this->assertSame([], ConfiguracionSegura::errores(true, 's3'));
        $this->assertSame([], ConfiguracionSegura::errores(false, 'public'), 'En desarrollo no se bloquea nada.');

        $this->assertNotEmpty(ConfiguracionSegura::avisos(true, 'log'));
        $this->assertNotEmpty(ConfiguracionSegura::avisos(true, 'array'));
        $this->assertSame([], ConfiguracionSegura::avisos(true, 'smtp'));
        $this->assertSame([], ConfiguracionSegura::avisos(false, 'log'));
    }

    // ---- B5 -----------------------------------------------------------------

    public function test_dompdf_no_abre_recursos_remotos_ni_ejecuta_php(): void
    {
        $this->assertFalse((bool) config('dompdf.options.enable_remote'), 'Con enable_remote, una URL en un PDF es un SSRF.');
        $this->assertFalse((bool) config('dompdf.options.enable_php'));
    }
}
