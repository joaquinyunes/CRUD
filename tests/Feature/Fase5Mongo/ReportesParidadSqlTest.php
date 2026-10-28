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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use MongoDB\BSON\Decimal128;
use MongoDB\BSON\UTCDateTime;
use Tests\TestCase;

/**
 * Fase 5: cada total de los reportes y del dashboard calculado con agregaciones de
 * MongoDB se compara contra la consulta SQL original (copiada tal cual) corrida sobre
 * una copia de los mismos datos en SQLite. Si un número difiere, falla.
 */
class ReportesParidadSqlTest extends TestCase
{
    use RefreshDatabase;

    private ReportesService $reportes;

    private Carbon $hoy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reportes = app(ReportesService::class);
        $this->hoy = Carbon::now();
        $this->cargarDatos();
        $this->copiarASqlite();
    }

    // ---- Datos de prueba (en Mongo) y su copia en SQLite ----

    private function cargarDatos(): void
    {
        $catA = Categoria::create(['nombre' => 'Herramientas', 'estado' => true]);
        $catB = Categoria::create(['nombre' => 'Pinturas', 'estado' => true]);

        $p1 = Producto::create(['codigo' => 'P1', 'nombre' => 'Martillo', 'categoria_id' => $catA->id, 'precio_compra' => 100, 'precio_venta' => 150, 'stock' => 50, 'stock_minimo' => 5, 'estado' => 'activo']);
        $p2 = Producto::create(['codigo' => 'P2', 'nombre' => 'Pintura', 'categoria_id' => $catB->id, 'precio_compra' => 50, 'precio_venta' => 80, 'stock' => 2, 'stock_minimo' => 5, 'estado' => 'activo']);
        $p3 = Producto::create(['codigo' => 'P3', 'nombre' => 'Clavo', 'categoria_id' => 9999, 'precio_compra' => 10, 'precio_venta' => 20, 'stock' => 0, 'stock_minimo' => 5, 'estado' => 'activo']);

        $c1 = Cliente::create(['nombre' => 'Ana', 'apellido' => 'Pérez', 'email' => 'ana@x.com', 'estado' => 'activo']);
        $c2 = Cliente::create(['nombre' => 'Luis', 'apellido' => 'Gómez', 'email' => 'luis@x.com', 'estado' => 'activo']);

        $v1 = Proveedor::create(['nombre' => 'Ferretería Sur', 'cuit' => '30111111118', 'email' => 'sur@x.com', 'telefono' => '111', 'estado' => 'activo']);
        $v2 = Proveedor::create(['nombre' => 'Mayorista Norte', 'cuit' => '30222222229', 'email' => 'norte@x.com', 'telefono' => '222', 'estado' => 'activo']);

        // [días atrás, cliente, estado, [[producto, cantidad, precio], ...]]
        $ventas = [
            [0, $c1, 'completada', [[$p1, 2, 150], [$p2, 1, 80]]],
            [1, $c2, 'completada', [[$p1, 1, 150]]],
            [9, $c1, 'completada', [[$p2, 3, 80], [$p3, 5, 20]]],
            [40, $c1, 'completada', [[$p1, 4, 150]]],
            [200, $c2, 'completada', [[$p3, 1, 20]]],
            [0, $c1, 'anulada', [[$p1, 10, 150]]],
            [0, $c2, 'pendiente', [[$p2, 2, 80]]],
        ];

        foreach ($ventas as $i => [$dias, $cliente, $estado, $lineas]) {
            $total = collect($lineas)->sum(fn ($l) => $l[1] * $l[2]);
            $venta = Venta::create([
                'numero'   => 'VTA-'.str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT), 'cliente_id' => $cliente->id,
                'fecha'    => $this->hoy->copy()->subDays($dias)->toDateString(), 'total' => $total, 'total_final' => $total,
                'subtotal' => $total, 'estado' => $estado, 'user_id' => 1,
            ]);
            foreach ($lineas as [$producto, $cantidad, $precio]) {
                VentaDetalle::create([
                    'venta_id' => $venta->id, 'producto_id' => $producto->id, 'cantidad' => $cantidad,
                    'precio'   => $precio, 'costo_unitario' => $producto->precio_compra, 'subtotal' => $cantidad * $precio,
                ]);
            }
        }

        // [días atrás, proveedor, estado, total]
        foreach ([[0, $v1, 'completada', 1000], [9, $v2, 'completada', 500], [40, $v1, 'completada', 250], [0, $v2, 'pendiente', 999]] as $i => [$dias, $proveedor, $estado, $total]) {
            Compra::create([
                'numero' => 'COM-'.str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT), 'proveedor_id' => $proveedor->id,
                'fecha'  => $this->hoy->copy()->subDays($dias)->toDateString(), 'subtotal' => $total, 'total' => $total,
                'estado' => $estado, 'user_id' => 1,
            ]);
        }

        foreach (['salida', 'salida', 'entrada', 'ajuste'] as $tipo) {
            MovimientoStock::create(['producto_id' => $p1->id, 'deposito_id' => 1, 'tipo' => $tipo, 'cantidad' => 1, 'user_id' => 1, 'referencia_tipo' => 'x']);
        }
    }

    /** Copia cada colección de Mongo a la tabla SQLite del mismo nombre (solo las columnas que existen). */
    private function copiarASqlite(): void
    {
        foreach (['categorias', 'productos', 'clientes', 'proveedores', 'ventas', 'ventas_detalle', 'compras', 'movimientos_stock'] as $tabla) {
            $columnas = array_flip(Schema::getColumnListing($tabla));

            foreach (DB::connection('mongodb')->getCollection($tabla)->find() as $doc) {
                $fila = [];
                foreach ($doc as $campo => $valor) {
                    $columna = $campo === '_id' ? 'id' : $campo;
                    if (! isset($columnas[$columna]) || is_object($valor) && ! $valor instanceof Decimal128 && ! $valor instanceof UTCDateTime) {
                        continue;
                    }
                    $fila[$columna] = match (true) {
                        $valor instanceof Decimal128  => (string) $valor,
                        $valor instanceof UTCDateTime => Carbon::instance($valor->toDateTime())->toDateTimeString(),
                        is_bool($valor)               => (int) $valor,
                        default                       => $valor,
                    };
                }
                DB::table($tabla)->insert($fila);
            }
        }
    }

    /** @param  iterable<object>  $filas */
    private function numeros(iterable $filas, array $campos): array
    {
        return collect($filas)->map(fn ($f) => collect($campos)->mapWithKeys(
            fn ($c) => [$c => is_numeric($f->$c) ? round((float) $f->$c, 2) : $f->$c]
        )->all())->values()->all();
    }

    // ---- Ventas y compras por período ----

    public function test_ventas_por_periodo_coinciden_con_el_sql(): void
    {
        $sql = fn (string $expr, string $columnaExtra = '') => DB::table('ventas')
            ->selectRaw("$expr as periodo, COUNT(*) as cantidad, SUM(total) as total")
            ->where('estado', 'completada')->where('fecha', '>=', $this->hoy->copy()->subDays(30)->toDateTimeString())
            ->groupBy(DB::raw($expr))->orderBy('periodo', 'desc')->get();

        $this->assertEquals(
            $this->numeros($sql("STRFTIME('%Y-%m-%d', fecha)"), ['periodo', 'cantidad', 'total']),
            $this->numeros($this->reportes->porPeriodo('ventas', 'diario', $this->hoy), ['periodo', 'cantidad', 'total'])
        );

        $mensualSql = DB::table('ventas')
            ->selectRaw("STRFTIME('%Y-%m', fecha) as periodo, COUNT(*) as cantidad, SUM(total) as total")
            ->where('estado', 'completada')->where('fecha', '>=', $this->hoy->copy()->subMonths(12)->toDateTimeString())
            ->groupBy(DB::raw("STRFTIME('%Y-%m', fecha)"))->orderBy('periodo', 'desc')->get();
        $this->assertEquals(
            $this->numeros($mensualSql, ['periodo', 'cantidad', 'total']),
            $this->numeros($this->reportes->porPeriodo('ventas', 'mensual', $this->hoy), ['periodo', 'cantidad', 'total'])
        );

        // Semanal: SQLite numera las semanas distinto que ISO, así que se comparan las cifras y el primer día.
        $semanalSql = DB::table('ventas')
            ->selectRaw("STRFTIME('%Y-%W', fecha) as semana, MIN(fecha) as periodo, COUNT(*) as cantidad, SUM(total) as total")
            ->where('estado', 'completada')->where('fecha', '>=', $this->hoy->copy()->subWeeks(12)->toDateTimeString())
            ->groupBy(DB::raw("STRFTIME('%Y-%W', fecha)"))->orderBy('semana', 'desc')->get()
            ->map(fn ($f) => (object) ['periodo' => substr($f->periodo, 0, 10), 'cantidad' => $f->cantidad, 'total' => $f->total]);
        $this->assertEquals(
            $this->numeros($semanalSql, ['periodo', 'cantidad', 'total']),
            $this->numeros($this->reportes->porPeriodo('ventas', 'semanal', $this->hoy), ['periodo', 'cantidad', 'total'])
        );
    }

    public function test_compras_por_periodo_coinciden_con_el_sql(): void
    {
        $sql = DB::table('compras')
            ->selectRaw("STRFTIME('%Y-%m-%d', fecha) as periodo, COUNT(*) as cantidad, SUM(total) as total")
            ->where('estado', 'completada')->where('fecha', '>=', $this->hoy->copy()->subDays(30)->toDateTimeString())
            ->groupBy(DB::raw("STRFTIME('%Y-%m-%d', fecha)"))->orderBy('periodo', 'desc')->get();

        $this->assertEquals(
            $this->numeros($sql, ['periodo', 'cantidad', 'total']),
            $this->numeros($this->reportes->porPeriodo('compras', 'diario', $this->hoy), ['periodo', 'cantidad', 'total'])
        );
        $this->assertSame(1500.0, (float) $this->reportes->porPeriodo('compras', 'diario', $this->hoy)->sum('total'));
    }

    // ---- Productos, clientes, proveedores ----

    public function test_productos_mas_vendidos_coinciden_con_el_sql(): void
    {
        $desde = $this->hoy->copy()->startOfYear()->toDateString();
        $hasta = $this->hoy->toDateString();

        $sql = DB::table('ventas_detalle')
            ->join('productos', 'ventas_detalle.producto_id', '=', 'productos.id')
            ->join('ventas', 'ventas_detalle.venta_id', '=', 'ventas.id')
            ->where('ventas.estado', 'completada')
            ->whereBetween('ventas.fecha', [$desde, $hasta.' 23:59:59'])
            ->select('productos.nombre', 'productos.codigo', DB::raw('SUM(ventas_detalle.cantidad) as total_vendido'), DB::raw('SUM(ventas_detalle.subtotal) as total_facturado'))
            ->groupBy('productos.id', 'productos.nombre', 'productos.codigo')
            ->orderBy('total_vendido', 'desc')->limit(10)->get();

        $this->assertEquals(
            $this->numeros($sql, ['nombre', 'codigo', 'total_vendido', 'total_facturado']),
            $this->numeros($this->reportes->productosMasVendidos($desde, $hasta, 10), ['nombre', 'codigo', 'total_vendido', 'total_facturado'])
        );
    }

    public function test_mejores_clientes_coinciden_con_el_sql(): void
    {
        $desde = $this->hoy->copy()->startOfYear()->toDateString();
        $hasta = $this->hoy->toDateString();

        $sql = DB::table('ventas')
            ->join('clientes', 'ventas.cliente_id', '=', 'clientes.id')
            ->where('ventas.estado', 'completada')
            ->whereBetween('ventas.fecha', [$desde, $hasta.' 23:59:59'])
            ->select('clientes.nombre', 'clientes.apellido', 'clientes.email', DB::raw('COUNT(*) as total_compras'), DB::raw('SUM(ventas.total) as total_gastado'))
            ->groupBy('clientes.id', 'clientes.nombre', 'clientes.apellido', 'clientes.email')
            ->orderBy('total_gastado', 'desc')->limit(10)->get();

        $campos = ['nombre', 'apellido', 'email', 'total_compras', 'total_gastado'];
        $this->assertEquals($this->numeros($sql, $campos), $this->numeros($this->reportes->mejoresClientes($desde, $hasta, 10), $campos));
    }

    public function test_ranking_de_proveedores_y_proveedores_con_compras_coinciden_con_el_sql(): void
    {
        $sql = DB::table('compras')
            ->join('proveedores', 'compras.proveedor_id', '=', 'proveedores.id')
            ->where('compras.estado', 'completada')
            ->select('proveedores.nombre', 'proveedores.email', 'proveedores.telefono', DB::raw('COUNT(*) as total_compras'), DB::raw('SUM(compras.total) as total_gastado'))
            ->groupBy('proveedores.id', 'proveedores.nombre', 'proveedores.email', 'proveedores.telefono')
            ->orderBy('total_gastado', 'desc')->limit(10)->get();

        $campos = ['nombre', 'email', 'telefono', 'total_compras', 'total_gastado'];
        $this->assertEquals($this->numeros($sql, $campos), $this->numeros($this->reportes->proveedoresRanking(10), $campos));

        $this->assertSame(
            DB::table('proveedores')->whereExists(fn ($q) => $q->select(DB::raw(1))->from('compras')->whereColumn('compras.proveedor_id', 'proveedores.id')->where('compras.estado', 'completada'))->count(),
            $this->reportes->proveedoresConCompras()
        );
    }

    // ---- Rentabilidad y dashboard ----

    public function test_rentabilidad_por_producto_coincide_con_el_sql(): void
    {
        $desde = $this->hoy->copy()->subDays(60)->toDateString();
        $hasta = $this->hoy->toDateString();

        // El SQL original dividía enteros con enteros en SQLite (350 / 1050 = 0): el oráculo fuerza
        // división real (`* 1.0`) para comparar contra el margen verdadero.
        $sql = DB::table('ventas_detalle')
            ->join('ventas', 'ventas_detalle.venta_id', '=', 'ventas.id')
            ->join('productos', 'ventas_detalle.producto_id', '=', 'productos.id')
            ->leftJoin('categorias', 'productos.categoria_id', '=', 'categorias.id')
            ->where('ventas.estado', 'completada')
            ->whereBetween('ventas.fecha', [$desde, $hasta.' 23:59:59'])
            ->select(
                'productos.codigo', 'productos.nombre', 'categorias.nombre as categoria', 'productos.precio_compra', 'productos.precio_venta',
                DB::raw('SUM(ventas_detalle.cantidad) as unidades_vendidas'),
                DB::raw('SUM(ventas_detalle.subtotal) as total_facturado'),
                DB::raw('SUM(ventas_detalle.cantidad * productos.precio_compra) as costo_total'),
                DB::raw('SUM(ventas_detalle.subtotal) - SUM(ventas_detalle.cantidad * productos.precio_compra) as ganancia_total'),
                DB::raw('CASE WHEN SUM(ventas_detalle.subtotal) > 0 THEN ((SUM(ventas_detalle.subtotal) - SUM(ventas_detalle.cantidad * productos.precio_compra)) * 1.0 / SUM(ventas_detalle.subtotal)) * 100 ELSE 0 END as margen_porcentaje')
            )
            ->groupBy('productos.id', 'productos.codigo', 'productos.nombre', 'productos.precio_compra', 'productos.precio_venta', 'categorias.nombre')
            ->orderBy('ganancia_total', 'desc')->get();

        $campos = ['codigo', 'nombre', 'categoria', 'precio_compra', 'precio_venta', 'unidades_vendidas', 'total_facturado', 'costo_total', 'ganancia_total', 'margen_porcentaje'];
        $this->assertEquals($this->numeros($sql, $campos), $this->numeros($this->reportes->rentabilidadPorProducto($desde, $hasta), $campos));
    }

    public function test_ganancia_bruta_top_rentables_y_graficos_del_dashboard_coinciden_con_el_sql(): void
    {
        $anio = $this->hoy->year;
        $mes = $this->hoy->month;

        $fila = DB::table('ventas_detalle')
            ->join('ventas', 'ventas.id', '=', 'ventas_detalle.venta_id')
            ->join('productos', 'productos.id', '=', 'ventas_detalle.producto_id')
            ->where('ventas.estado', 'completada')->whereYear('ventas.fecha', $anio)->whereMonth('ventas.fecha', $mes)
            ->selectRaw('COALESCE(SUM(ventas_detalle.subtotal), 0) as facturado')
            ->selectRaw('COALESCE(SUM(ventas_detalle.cantidad * productos.precio_compra), 0) as costo')
            ->first();
        $this->assertSame(round((float) $fila->facturado - (float) $fila->costo, 2), $this->reportes->gananciaBruta($anio, $mes));
        $this->assertSame(0.0, $this->reportes->gananciaBruta(2001, 1));

        $top = DB::table('ventas_detalle')
            ->join('ventas', 'ventas.id', '=', 'ventas_detalle.venta_id')
            ->join('productos', 'productos.id', '=', 'ventas_detalle.producto_id')
            ->where('ventas.estado', 'completada')->whereMonth('ventas.fecha', $mes)->whereYear('ventas.fecha', $anio)
            ->select('productos.nombre', DB::raw('SUM(ventas_detalle.cantidad) as vendidos'),
                DB::raw('SUM(ventas_detalle.subtotal) - SUM(ventas_detalle.cantidad * productos.precio_compra) as ganancia'))
            ->groupBy('productos.id', 'productos.nombre')->orderByDesc('ganancia')->limit(5)->get();
        $this->assertEquals($this->numeros($top, ['nombre', 'vendidos', 'ganancia']), $this->numeros($this->reportes->topRentables($anio, $mes, 5), ['nombre', 'vendidos', 'ganancia']));

        $desde = $this->hoy->copy()->subDays(30)->toDateString();
        $hasta = $this->hoy->toDateString();

        $diarias = DB::table('ventas')->where('estado', 'completada')->whereBetween('fecha', [$desde, $hasta.' 23:59:59'])
            ->selectRaw("STRFTIME('%Y-%m-%d', fecha) as fecha, SUM(total) as total")
            ->groupBy(DB::raw("STRFTIME('%Y-%m-%d', fecha)"))->orderBy(DB::raw("STRFTIME('%Y-%m-%d', fecha)"))->get();
        $this->assertEquals($this->numeros($diarias, ['fecha', 'total']), $this->numeros($this->reportes->ventasDiarias($desde, $hasta), ['fecha', 'total']));

        $porCantidad = DB::table('ventas_detalle')
            ->join('ventas', 'ventas.id', '=', 'ventas_detalle.venta_id')
            ->join('productos', 'productos.id', '=', 'ventas_detalle.producto_id')
            ->where('ventas.estado', 'completada')->whereBetween('ventas.fecha', [$desde, $hasta.' 23:59:59'])
            ->selectRaw('productos.nombre, SUM(ventas_detalle.cantidad) as cantidad')
            ->groupBy('productos.nombre')->orderByDesc('cantidad')->limit(5)->get();
        $this->assertEquals($this->numeros($porCantidad, ['nombre', 'cantidad']), $this->numeros($this->reportes->topProductosPorCantidad($desde, $hasta, 5), ['nombre', 'cantidad']));

        $movimientos = MovimientoStock::whereMonth('created_at', $mes)->whereYear('created_at', $anio)
            ->get()->groupBy('tipo')->map(fn ($g) => $g->count())->sortKeys()->all();
        $this->assertEquals($movimientos, $this->reportes->movimientosDelMes($anio, $mes)->pluck('cantidad', 'tipo')->all());
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
