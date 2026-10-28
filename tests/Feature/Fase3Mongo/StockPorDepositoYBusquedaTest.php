<?php

namespace Tests\Feature\Fase3Mongo;

use App\Exceptions\StockInsuficienteException;
use App\Models\Cliente;
use App\Models\Deposito;
use App\Models\Producto;
use App\Models\Venta;
use App\Services\StockService;
use App\Support\Mongo\StockDeposito;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 3: lo que reemplaza a las consultas SQL de depósitos y a los whereHas de ventas.
 */
class StockPorDepositoYBusquedaTest extends TestCase
{
    use RefreshDatabase;

    public function test_los_totales_y_el_stock_por_deposito_siguen_a_las_transferencias(): void
    {
        $principal = Deposito::principalId();
        $sucursal = Deposito::create(['nombre' => 'Sucursal', 'es_principal' => false, 'activo' => true])->id;
        $a = Producto::factory()->create(['stock' => 10]);
        $b = Producto::factory()->create(['stock' => 4]);

        app(StockService::class)->transferir($a, 3, $principal, $sucursal);

        $this->assertEquals([$principal => 11, $sucursal => 3], StockDeposito::totalesPorDeposito());
        $this->assertEquals([$a->id => 7, $b->id => 4], StockDeposito::cantidadesDe($principal));
        $this->assertSame([$a->id => 3], StockDeposito::cantidadesDe($sucursal));
        $this->assertSame(10, $a->fresh()->stock, 'Una transferencia no cambia el total del producto.');
    }

    public function test_no_se_transfiere_mas_de_lo_que_hay_en_el_origen(): void
    {
        $sucursal = Deposito::create(['nombre' => 'Sucursal', 'es_principal' => false, 'activo' => true])->id;
        $producto = Producto::factory()->create(['stock' => 2]);

        $this->expectException(StockInsuficienteException::class);

        try {
            app(StockService::class)->transferir($producto, 5, Deposito::principalId(), $sucursal);
        } finally {
            $this->assertSame(2, $producto->stockEn(Deposito::principalId()));
            $this->assertSame(0, $producto->stockEn($sucursal));
        }
    }

    public function test_buscar_ventas_por_nombre_de_cliente_o_por_numero(): void
    {
        $ana = Cliente::factory()->create(['nombre' => 'Ana', 'apellido' => 'Pérez']);
        $luis = Cliente::factory()->create(['nombre' => 'Luis', 'apellido' => 'Gómez']);
        $base = ['fecha' => now(), 'total' => 1, 'estado' => 'pendiente'];
        $deAna = Venta::create($base + ['numero' => 'VTA-00001', 'cliente_id' => $ana->id]);
        $deLuis = Venta::create($base + ['numero' => 'VTA-00002', 'cliente_id' => $luis->id]);

        $this->assertSame([$deAna->id], Venta::buscar('Pérez')->get()->modelKeys());
        $this->assertSame([$deLuis->id], Venta::buscar('VTA-00002')->get()->modelKeys());
        $this->assertSame([], Venta::buscar('nadie')->get()->modelKeys());
    }
}
