<?php

namespace Tests\Feature\Fase1Mongo;

use App\Models\CajaMovimiento;
use App\Models\CajaSesion;
use App\Models\Cliente;
use App\Models\Deposito;
use App\Models\MetodoPago;
use App\Models\ModeloMongo;
use App\Models\MovimientoStock;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Models\VentaPago;
use App\Support\Mongo\Indices;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Fase 1: los índices únicos (que reemplazan a las migraciones) y el trait
 * LimpiaMongo que deja cada test con la base vacía pero con sus índices.
 */
class IndicesYLimpiezaTest extends TestCase
{
    /** @return list<class-string<ModeloMongo>> */
    private function modelosPortados(): array
    {
        return [
            Producto::class, Cliente::class, Deposito::class, Venta::class, VentaDetalle::class,
            VentaPago::class, MovimientoStock::class, CajaSesion::class, CajaMovimiento::class, MetodoPago::class,
        ];
    }

    /** @return list<string> nombres de los índices de una colección */
    private function indices(string $coleccion): array
    {
        $nombres = [];
        foreach (DB::connection('mongodb')->getCollection($coleccion)->listIndexes() as $indice) {
            $nombres[$indice->getName()] = $indice->isUnique();
        }

        return $nombres;
    }

    public function test_el_numero_de_venta_es_unico_en_la_base(): void
    {
        $datos = ['numero' => 'VTA-00001', 'cliente_id' => 1, 'fecha' => now(), 'total' => 1, 'estado' => 'pendiente'];
        Venta::create($datos);

        $this->expectException(\Throwable::class);
        $this->expectExceptionMessageMatches('/E11000|duplicate key/i');
        Venta::create($datos);
    }

    public function test_el_codigo_de_producto_es_unico_en_la_base(): void
    {
        Producto::factory()->create(['codigo' => 'ABC-1']);

        $this->expectException(\Throwable::class);
        $this->expectExceptionMessageMatches('/E11000|duplicate key/i');
        Producto::factory()->create(['codigo' => 'ABC-1']);
    }

    public function test_crear_los_indices_es_idempotente_y_no_toca_los_datos(): void
    {
        Producto::factory()->create(['codigo' => 'ABC-2']);
        $antes = $this->indices('ventas');

        Indices::crear();
        Indices::crear();

        $this->assertSame($antes, $this->indices('ventas'));
        $this->assertSame(1, Producto::count());
        $this->assertTrue($this->indices('ventas')['numero_unico']);
    }

    public function test_el_comando_mongo_indices_los_crea(): void
    {
        DB::connection('mongodb')->getCollection('ventas')->dropIndexes();
        $this->assertArrayNotHasKey('numero_unico', $this->indices('ventas'));

        $this->artisan('mongo:indices')->assertSuccessful();

        $this->assertArrayHasKey('numero_unico', $this->indices('ventas'));
    }

    public function test_limpia_mongo_vacia_los_datos_pero_conserva_los_indices(): void
    {
        $this->crearUnaVenta();

        $this->setUpLimpiaMongo();

        $this->assertSame(0, DB::connection('mongodb')->table('ventas')->count());
        // Los contadores arrancan de cero: solo quedan los del depósito recién sembrado y su auditoría.
        $contadores = DB::connection('mongodb')->getCollection('contadores')->distinct('_id');
        $this->assertEqualsCanonicalizing(['id:depositos', 'id:auditoria'], $contadores);
        $this->assertArrayHasKey('numero_unico', $this->indices('ventas'));
        // Queda el depósito principal (en SQL lo sembraba una migración) y los ids arrancan de nuevo.
        $this->assertSame(1, Deposito::principalId());
        $this->assertSame(1, Producto::factory()->create()->id);
    }

    private function crearUnaVenta(): void
    {
        Venta::create(['numero' => 'VTA-00001', 'cliente_id' => 1, 'fecha' => now(), 'total' => 1, 'estado' => 'pendiente']);
        $this->assertSame(1, Venta::count());
    }

    // ---- D6: ningún modelo de Mongo guarda dinero como float ----

    public function test_ningun_modelo_portado_castea_a_float(): void
    {
        foreach ($this->modelosPortados() as $clase) {
            foreach ((new $clase)->getCasts() as $campo => $cast) {
                $this->assertDoesNotMatchRegularExpression(
                    '/^(float|double|real)$/',
                    (string) $cast,
                    "{$clase}::\${$campo} usa {$cast}: el dinero va como decimal:2 (Decimal128), nunca float."
                );
            }
        }
    }

    public function test_los_modelos_portados_son_modelos_mongo_con_id_numerico(): void
    {
        foreach ($this->modelosPortados() as $clase) {
            $modelo = new $clase;
            $this->assertInstanceOf(ModeloMongo::class, $modelo, $clase);
            $this->assertSame('int', $modelo->getKeyType(), $clase);
            $this->assertFalse($modelo->getIncrementing(), $clase);
        }
    }
}
