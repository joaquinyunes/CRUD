<?php

namespace Tests\Feature\Fase5Mongo;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\MovimientoStock;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Services\ReportesService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Fase 5: cada total de los reportes y del dashboard, calculado con agregaciones de
 * MongoDB, se compara contra el mismo cálculo hecho en PHP puro sobre los datos que
 * armó el test. (Mientras se portaba, estos mismos totales se compararon contra el SQL
 * original corrido sobre SQLite y coincidieron; sin SQL en el proyecto, el oráculo es este.)
 */
class ReportesCoincidenConElCalculoTest extends TestCase
{
    private ReportesService $reportes;

    private Carbon $hoy;

    /** @var array<string,Producto> */
    private array $productos;

    /** @var list<array{int,Cliente,string,list<array{Producto,int,int}>}> días atrás, cliente, estado, renglones */
    private array $ventas;

    /** @var list<array{int,Proveedor,string,int}> días atrás, proveedor, estado, total */
    private array $compras;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reportes = app(ReportesService::class);
        $this->hoy = Carbon::now();
        $this->cargarDatos();
    }

    private function cargarDatos(): void
    {
        $catA = Categoria::create(['nombre' => 'Herramientas', 'estado' => true]);
        $catB = Categoria::create(['nombre' => 'Pinturas', 'estado' => true]);

        $p1 = Producto::create(['codigo' => 'P1', 'nombre' => 'Martillo', 'categoria_id' => $catA->id, 'precio_compra' => 100, 'precio_venta' => 150, 'stock' => 50, 'stock_minimo' => 5, 'estado' => 'activo']);
        $p2 = Producto::create(['codigo' => 'P2', 'nombre' => 'Pintura', 'categoria_id' => $catB->id, 'precio_compra' => 50, 'precio_venta' => 80, 'stock' => 2, 'stock_minimo' => 5, 'estado' => 'activo']);
        // Su categoría ya no existe: el reporte la muestra vacía (era un LEFT JOIN).
        $p3 = Producto::create(['codigo' => 'P3', 'nombre' => 'Clavo', 'categoria_id' => 9999, 'precio_compra' => 10, 'precio_venta' => 20, 'stock' => 0, 'stock_minimo' => 5, 'estado' => 'activo']);
        $this->productos = ['P1' => $p1, 'P2' => $p2, 'P3' => $p3];

        $c1 = Cliente::create(['nombre' => 'Ana', 'apellido' => 'Pérez', 'email' => 'ana@x.com', 'estado' => 'activo']);
        $c2 = Cliente::create(['nombre' => 'Luis', 'apellido' => 'Gómez', 'email' => 'luis@x.com', 'estado' => 'activo']);

        $v1 = Proveedor::create(['nombre' => 'Ferretería Sur', 'cuit' => '30111111118', 'email' => 'sur@x.com', 'telefono' => '111', 'estado' => 'activo']);
        $v2 = Proveedor::create(['nombre' => 'Mayorista Norte', 'cuit' => '30222222229', 'email' => 'norte@x.com', 'telefono' => '222', 'estado' => 'activo']);

        $this->ventas = [
            [0, $c1, 'completada', [[$p1, 2, 150], [$p2, 1, 80]]],
            [1, $c2, 'completada', [[$p1, 1, 150]]],
            [9, $c1, 'completada', [[$p2, 3, 80], [$p3, 5, 20]]],
            [40, $c1, 'completada', [[$p1, 4, 150]]],
            [200, $c2, 'completada', [[$p3, 1, 20]]],
            [0, $c1, 'anulada', [[$p1, 10, 150]]],
            [0, $c2, 'pendiente', [[$p2, 2, 80]]],
        ];
        $this->compras = [[0, $v1, 'completada', 1000], [9, $v2, 'completada', 500], [40, $v1, 'completada', 250], [0, $v2, 'pendiente', 999]];

        foreach ($this->ventas as $i => [$dias, $cliente, $estado, $lineas]) {
            $total = collect($lineas)->sum(fn ($l) => $l[1] * $l[2]);
            $venta = Venta::create([
                'numero'   => 'VTA-'.str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT), 'cliente_id' => $cliente->id,
                'fecha'    => $this->dia($dias), 'total' => $total, 'total_final' => $total,
                'subtotal' => $total, 'estado' => $estado, 'user_id' => 1,
            ]);
            foreach ($lineas as [$producto, $cantidad, $precio]) {
                VentaDetalle::create([
                    'venta_id' => $venta->id, 'producto_id' => $producto->id, 'cantidad' => $cantidad,
                    'precio'   => $precio, 'costo_unitario' => $producto->precio_compra, 'subtotal' => $cantidad * $precio,
                ]);
            }
        }

        foreach ($this->compras as $i => [$dias, $proveedor, $estado, $total]) {
            Compra::create([
                'numero' => 'COM-'.str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT), 'proveedor_id' => $proveedor->id,
                'fecha'  => $this->dia($dias), 'subtotal' => $total, 'total' => $total,
                'estado' => $estado, 'user_id' => 1,
            ]);
        }

        foreach (['salida', 'salida', 'entrada', 'ajuste'] as $tipo) {
            MovimientoStock::create(['producto_id' => $p1->id, 'deposito_id' => 1, 'tipo' => $tipo, 'cantidad' => 1, 'user_id' => 1, 'referencia_tipo' => 'x']);
        }
    }

    private function dia(int $atras): string
    {
        return $this->hoy->copy()->subDays($atras)->toDateString();
    }

    // ---- El oráculo: lo mismo, en PHP ----

    /** @return Collection<int,array{fecha:Carbon,total:int,cliente:Cliente,lineas:list<array>}> ventas completadas */
    private function completadas(): Collection
    {
        return collect($this->ventas)->filter(fn ($v) => $v[2] === 'completada')->map(fn ($v) => [
            'fecha'   => $this->hoy->copy()->subDays($v[0])->startOfDay(),
            'total'   => collect($v[3])->sum(fn ($l) => $l[1] * $l[2]),
            'cliente' => $v[1],
            'lineas'  => $v[3],
        ])->values();
    }

    /** @return Collection<int,array{producto:Producto,cantidad:int,subtotal:int}> renglones de ventas completadas entre dos fechas */
    private function renglones(?Carbon $desde = null, ?Carbon $hasta = null): Collection
    {
        return $this->completadas()
            ->filter(fn ($v) => (! $desde || $v['fecha']->gte($desde->copy()->startOfDay())) && (! $hasta || $v['fecha']->lte($hasta->copy()->startOfDay())))
            ->flatMap(fn ($v) => collect($v['lineas'])->map(fn ($l) => ['producto' => $l[0], 'cantidad' => $l[1], 'subtotal' => $l[1] * $l[2]]));
    }

    /** @return list<array{string,int,int}> [período, cantidad, total] */
    private function agrupar(Collection $documentos, string $formato, Carbon $desde): array
    {
        return $documentos->filter(fn ($d) => $d['fecha']->gte($desde))
            ->groupBy(fn ($d) => $d['fecha']->format($formato))
            ->map(fn ($g, $periodo) => [(string) $periodo, $g->count(), $g->sum('total')])
            ->sortByDesc(0)->values()->all();
    }

    private function normalizar(Collection $filas, array $campos): array
    {
        return $filas->map(fn ($f) => collect($campos)->map(fn ($c) => is_numeric($f->$c) ? round((float) $f->$c, 2) : $f->$c)->all())->values()->all();
    }

    private function comoLista(Collection $filas, array $campos): array
    {
        return $filas->map(fn ($f) => collect($campos)->map(fn ($c) => is_numeric($f->$c) ? round((float) $f->$c, 2) : $f->$c)->all())->all();
    }

    // ---- Ventas y compras por período ----

    public function test_ventas_por_periodo(): void
    {
        $filas = fn (string $periodo) => $this->reportes->porPeriodo('ventas', $periodo, $this->hoy)->map(fn ($f) => [$f->periodo, $f->cantidad, (float) $f->total])->all();
        $esperado = fn (string $formato, Carbon $desde) => collect($this->agrupar($this->completadas(), $formato, $desde))->map(fn ($f) => [$f[0], $f[1], (float) $f[2]])->all();

        $this->assertEquals($esperado('Y-m-d', $this->hoy->copy()->subDays(30)), $filas('diario'));
        $this->assertEquals($esperado('Y-m', $this->hoy->copy()->subMonths(12)), $filas('mensual'));

        // Semanal: ISO año-semana; se compara también el primer día de cada semana.
        $semanal = $this->reportes->porPeriodo('ventas', 'semanal', $this->hoy);
        $esperadoSemanal = $this->agrupar($this->completadas(), 'o-W', $this->hoy->copy()->subWeeks(12));
        $this->assertSame(array_column($esperadoSemanal, 0), $semanal->pluck('semana')->all());
        $this->assertEquals(array_column($esperadoSemanal, 2), $semanal->pluck('total')->map(fn ($t) => (int) $t)->all());
        $this->assertSame(array_column($esperadoSemanal, 1), $semanal->pluck('cantidad')->all());
    }

    public function test_compras_por_periodo(): void
    {
        $completadas = collect($this->compras)->filter(fn ($c) => $c[2] === 'completada')
            ->map(fn ($c) => ['fecha' => $this->hoy->copy()->subDays($c[0])->startOfDay(), 'total' => $c[3]])->values();
        $esperado = collect($this->agrupar($completadas, 'Y-m-d', $this->hoy->copy()->subDays(30)))->map(fn ($f) => [$f[0], $f[1], (float) $f[2]])->all();

        $real = $this->reportes->porPeriodo('compras', 'diario', $this->hoy)->map(fn ($f) => [$f->periodo, $f->cantidad, (float) $f->total])->all();

        $this->assertEquals($esperado, $real);
        $this->assertSame(1500.0, (float) $this->reportes->porPeriodo('compras', 'diario', $this->hoy)->sum('total'));
    }

    // ---- Productos, clientes, proveedores ----

    public function test_productos_mas_vendidos(): void
    {
        $desde = $this->hoy->copy()->startOfYear();
        $esperado = $this->renglones($desde, $this->hoy)->groupBy(fn ($r) => $r['producto']->codigo)
            ->map(fn ($g) => [$g[0]['producto']->nombre, $g[0]['producto']->codigo, $g->sum('cantidad'), (float) $g->sum('subtotal')])
            ->sortByDesc(2)->values()->all();

        $real = $this->reportes->productosMasVendidos($desde->toDateString(), $this->hoy->toDateString(), 10)
            ->map(fn ($f) => [$f->nombre, $f->codigo, $f->total_vendido, $f->total_facturado])->all();

        $this->assertEquals($esperado, $real);
    }

    public function test_mejores_clientes(): void
    {
        $desde = $this->hoy->copy()->startOfYear();
        $esperado = $this->completadas()->filter(fn ($v) => $v['fecha']->gte($desde))->groupBy(fn ($v) => $v['cliente']->id)
            ->map(fn ($g) => [$g[0]['cliente']->nombre, $g[0]['cliente']->apellido, $g[0]['cliente']->email, $g->count(), (float) $g->sum('total')])
            ->sortByDesc(4)->values()->all();

        $real = $this->reportes->mejoresClientes($desde->toDateString(), $this->hoy->toDateString(), 10)
            ->map(fn ($f) => [$f->nombre, $f->apellido, $f->email, $f->total_compras, $f->total_gastado])->all();

        $this->assertEquals($esperado, $real);
    }

    public function test_ranking_de_proveedores_y_proveedores_con_compras(): void
    {
        $esperado = collect($this->compras)->filter(fn ($c) => $c[2] === 'completada')->groupBy(fn ($c) => $c[1]->id)
            ->map(fn ($g) => [$g[0][1]->nombre, $g[0][1]->email, $g[0][1]->telefono, $g->count(), (float) $g->sum(3)])
            ->sortByDesc(4)->values()->all();

        $real = $this->reportes->proveedoresRanking(10)->map(fn ($f) => [$f->nombre, $f->email, $f->telefono, $f->total_compras, $f->total_gastado])->all();

        $this->assertEquals($esperado, $real);
        $this->assertSame(2, $this->reportes->proveedoresConCompras());
    }

    // ---- Rentabilidad y dashboard ----

    public function test_rentabilidad_por_producto(): void
    {
        $desde = $this->hoy->copy()->subDays(60);
        $esperado = $this->renglones($desde, $this->hoy)->groupBy(fn ($r) => $r['producto']->codigo)->map(function ($g) {
            $p = $g[0]['producto'];
            $facturado = (float) $g->sum('subtotal');
            $costo = (float) $g->sum(fn ($r) => $r['cantidad'] * $r['producto']->precio_compra);

            return [$p->codigo, $p->nombre, $p->categoria_id === 9999 ? null : Categoria::find($p->categoria_id)->nombre, (float) $p->precio_compra, (float) $p->precio_venta,
                $g->sum('cantidad'), $facturado, $costo, $facturado - $costo, $facturado > 0 ? ($facturado - $costo) / $facturado * 100 : 0];
        })->sortByDesc(8)->values()->all();

        $real = $this->reportes->rentabilidadPorProducto($desde->toDateString(), $this->hoy->toDateString())->map(fn ($f) => [
            $f->codigo, $f->nombre, $f->categoria, $f->precio_compra, $f->precio_venta, $f->unidades_vendidas,
            $f->total_facturado, $f->costo_total, $f->ganancia_total, $f->margen_porcentaje,
        ])->all();

        $this->assertEqualsWithDelta($esperado, $real, 0.01);
    }

    public function test_ganancia_bruta_top_rentables_y_graficos_del_dashboard(): void
    {
        $anio = $this->hoy->year;
        $mes = $this->hoy->month;
        $delMes = fn () => $this->renglones(Carbon::create($anio, $mes, 1), Carbon::create($anio, $mes, 1)->endOfMonth());

        $ganancia = fn (Collection $g) => $g->sum('subtotal') - $g->sum(fn ($r) => $r['cantidad'] * $r['producto']->precio_compra);

        $this->assertSame((float) $ganancia($delMes()), $this->reportes->gananciaBruta($anio, $mes));
        $this->assertSame(0.0, $this->reportes->gananciaBruta(2001, 1));

        $esperadoTop = $delMes()->groupBy(fn ($r) => $r['producto']->codigo)
            ->map(fn ($g) => [$g[0]['producto']->nombre, $g->sum('cantidad'), (float) $ganancia($g)])->sortByDesc(2)->take(5)->values()->all();
        $this->assertEquals($esperadoTop, $this->reportes->topRentables($anio, $mes, 5)->map(fn ($f) => [$f->nombre, $f->vendidos, $f->ganancia])->all());

        $desde = $this->hoy->copy()->subDays(30);
        $diarias = $this->completadas()->filter(fn ($v) => $v['fecha']->gte($desde->copy()->startOfDay()))->groupBy(fn ($v) => $v['fecha']->toDateString())
            ->map(fn ($g, $fecha) => [(string) $fecha, (float) $g->sum('total')])->sortKeys()->values()->all();
        $this->assertEquals($diarias, $this->reportes->ventasDiarias($desde->toDateString(), $this->hoy->toDateString())->map(fn ($f) => [$f->fecha, $f->total])->all());

        $porCantidad = $this->renglones($desde, $this->hoy)->groupBy(fn ($r) => $r['producto']->nombre)
            ->map(fn ($g, $nombre) => [(string) $nombre, $g->sum('cantidad')])->sortByDesc(1)->take(5)->values()->all();
        $this->assertEquals($porCantidad, $this->reportes->topProductosPorCantidad($desde->toDateString(), $this->hoy->toDateString(), 5)->map(fn ($f) => [$f->nombre, $f->cantidad])->all());

        $this->assertEquals(
            ['ajuste' => 1, 'entrada' => 1, 'salida' => 2],
            $this->reportes->movimientosDelMes($anio, $mes)->pluck('cantidad', 'tipo')->all()
        );
    }

    // ---- Pantallas ----

    public function test_las_pantallas_de_reportes_y_dashboard_se_muestran_con_estos_datos(): void
    {
        $admin = User::factory()->create(['role_id' => $this->rolAdministrador()->id]);

        foreach (['reportes.index', 'reportes.productos-vendidos', 'reportes.mejores-clientes', 'reportes.stock-critico', 'reportes.ganancias', 'reportes.proveedores-ranking', 'dashboard'] as $ruta) {
            $this->actingAs($admin)->get(route($ruta))->assertOk();
        }
        foreach (['diario', 'semanal', 'mensual'] as $periodo) {
            $this->actingAs($admin)->get(route('reportes.ventas-periodo', ['periodo' => $periodo]))->assertOk();
            $this->actingAs($admin)->get(route('reportes.compras-periodo', ['periodo' => $periodo]))->assertOk();
        }

        $respuesta = $this->actingAs($admin)->get(route('dashboard'));
        $this->assertSame($this->reportes->gananciaBruta($this->hoy->year, $this->hoy->month), $respuesta->viewData('gananciaMes'));
        $this->assertSame(1, $respuesta->viewData('ventasHoy'));
    }
}
