<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use App\Support\LectorCsv;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Los CSV que llegan son los que guarda Excel: con punto y coma, con BOM, en
 * Windows-1252, con una línea en blanco al final. Antes todos fallaban.
 */
class ImportarCsvTest extends TestCase
{
    use RefreshDatabase;

    private function importar(string $tipo, string $contenido)
    {
        $admin = User::factory()->create(['role_id' => $this->rolAdministrador()->id]);

        return $this->actingAs($admin)->post(route('importar.importar'), [
            'tipo'    => $tipo,
            'archivo' => UploadedFile::fake()->createWithContent('datos.csv', $contenido),
        ]);
    }

    public function test_csv_de_excel_con_bom_punto_y_coma_y_linea_final_en_blanco(): void
    {
        $csv = "\xEF\xBB\xBFnombre;codigo;precio_compra;precio_venta;stock\r\n"
            ."Yerba 1kg;YER-1;1.234,50;2.000,99;12\r\n"
            ."Fideos;FID-1;300;450,5;7\r\n"
            ."\r\n";

        $this->importar('productos', $csv)->assertRedirect()->assertSessionHas('success');

        $yerba = Producto::where('codigo', 'YER-1')->firstOrFail();
        $this->assertSame('Yerba 1kg', $yerba->nombre);
        $this->assertEquals(1234.50, $yerba->precio_compra);
        $this->assertEquals(2000.99, $yerba->precio_venta);
        $this->assertSame(12, $yerba->stock);
        $this->assertEquals(450.5, Producto::where('codigo', 'FID-1')->value('precio_venta'));
    }

    public function test_csv_en_windows_1252_conserva_los_acentos(): void
    {
        $csv = mb_convert_encoding("nombre;codigo\nCafé molído;CAF-1\n", 'Windows-1252', 'UTF-8');

        $this->importar('productos', $csv)->assertSessionHas('success');

        $this->assertSame('Café molído', Producto::where('codigo', 'CAF-1')->value('nombre'));
    }

    public function test_una_fila_corta_o_con_error_no_frena_a_las_demas(): void
    {
        $csv = "nombre,email,documento\nAna,ana@x.com,111\nBeto\n,sin@nombre.com,333\nCata,correo-roto,444\nDiego,diego@x.com,555\n";

        $respuesta = $this->importar('clientes', $csv);

        $respuesta->assertSessionHas('success', fn ($m) => str_contains($m, '3 registros importados') && str_contains($m, '2 errores'));
        $this->assertEqualsCanonicalizing(['Ana', 'Beto', 'Diego'], Cliente::pluck('nombre')->all());
        $respuesta->assertSessionHas('errores', fn ($e) => count($e) === 2 && str_contains($e[1], 'Fila 5'));
    }

    public function test_reimportar_la_misma_planilla_actualiza_en_lugar_de_duplicar(): void
    {
        $csv = "nombre,documento,telefono\nAna,111,1\n";
        $this->importar('clientes', $csv);
        $this->importar('clientes', "nombre,documento,telefono\nAna Maria,111,2\n");

        $this->assertSame(1, Cliente::count());
        $this->assertSame('Ana Maria', Cliente::firstOrFail()->nombre);

        $this->importar('proveedores', "nombre,cuit\nDistri,30111111118\n");
        $this->importar('proveedores', "nombre,cuit\nDistri SA,30111111118\n");

        $this->assertSame(1, Proveedor::count());
        $this->assertSame('Distri SA', Proveedor::firstOrFail()->nombre);
    }

    public function test_los_productos_sin_categoria_van_a_una_por_defecto_y_las_nuevas_se_crean(): void
    {
        $this->importar('productos', 'nombre,codigo,categoria
A,A-1,
B,B-1,Bebidas
C,C-1,Bebidas
')
            ->assertSessionHas('success', fn ($m) => str_contains($m, '3 registros'));

        $this->assertSame('Sin categoría', Producto::where('codigo', 'A-1')->firstOrFail()->categoria->nombre);
        $this->assertSame(Producto::where('codigo', 'B-1')->value('categoria_id'), Producto::where('codigo', 'C-1')->value('categoria_id'));
        $this->assertSame(2, Categoria::count());
    }

    public function test_numeros_como_los_escribe_una_persona(): void
    {
        $this->assertSame(1234.5, LectorCsv::numero('1.234,50'));
        $this->assertSame(1234.5, LectorCsv::numero('1,234.50'));
        $this->assertSame(12.5, LectorCsv::numero('12,5'));
        $this->assertSame(1234.5, LectorCsv::numero('1234.5'));
        $this->assertSame(1500.0, LectorCsv::numero('$ 1500'));
        $this->assertSame(-3.0, LectorCsv::numero('-3'));
        $this->assertSame(0.0, LectorCsv::numero(''));
        $this->assertSame(0.0, LectorCsv::numero(null));
    }
}
