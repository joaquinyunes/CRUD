<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReporteController extends Controller
{
    public function index(): View
    {
        $hoy = Carbon::now();

        $ventasHoy = Venta::whereDate('fecha', $hoy)->where('estado', 'completada')->sum('total');
        $ventasMes = Venta::whereMonth('fecha', $hoy->month)
            ->whereYear('fecha', $hoy->year)
            ->where('estado', 'completada')
            ->sum('total');

        $comprasMes = Compra::whereMonth('fecha', $hoy->month)
            ->whereYear('fecha', $hoy->year)
            ->where('estado', 'completada')
            ->sum('total');

        $totalVentasMes = Venta::whereMonth('fecha', $hoy->month)
            ->whereYear('fecha', $hoy->year)
            ->where('estado', 'completada')
            ->count();

        $productosStockCritico = Producto::where('estado', 'activo')
            ->whereColumn('stock', '<=', 'stock_minimo')
            ->count();

        $productosAgotados = Producto::where('estado', 'activo')
            ->where('stock', 0)
            ->count();

        $totalClientes = Cliente::where('estado', 'activo')->count();

        $totalProveedores = Proveedor::whereHas('compras', function ($q) {
            $q->where('estado', 'completada');
        })->count();

        return view('reportes.index', compact(
            'ventasHoy',
            'ventasMes',
            'comprasMes',
            'totalVentasMes',
            'productosStockCritico',
            'productosAgotados',
            'totalClientes',
            'totalProveedores'
        ));
    }

    public function ventasPorPeriodo(Request $request): View
    {
        $periodo = $request->get('periodo', 'diario');
        $hoy = Carbon::now();

        if ($periodo === 'diario') {
            $datos = Venta::select(
                DB::raw('DATE(fecha) as periodo'),
                DB::raw('COUNT(*) as cantidad'),
                DB::raw('SUM(total) as total')
            )
                ->where('estado', 'completada')
                ->where('fecha', '>=', $hoy->copy()->subDays(30))
                ->groupBy(DB::raw('DATE(fecha)'))
                ->orderBy('periodo', 'desc')
                ->get();
        } elseif ($periodo === 'semanal') {
            $datos = Venta::select(
                DB::raw('YEARWEEK(fecha, 1) as semana'),
                DB::raw('MIN(fecha) as periodo'),
                DB::raw('COUNT(*) as cantidad'),
                DB::raw('SUM(total) as total')
            )
                ->where('estado', 'completada')
                ->where('fecha', '>=', $hoy->copy()->subWeeks(12))
                ->groupBy(DB::raw('YEARWEEK(fecha, 1)'))
                ->orderBy('semana', 'desc')
                ->get();
        } else {
            $datos = Venta::select(
                DB::raw("DATE_FORMAT(fecha, '%Y-%m') as periodo"),
                DB::raw('COUNT(*) as cantidad'),
                DB::raw('SUM(total) as total')
            )
                ->where('estado', 'completada')
                ->where('fecha', '>=', $hoy->copy()->subMonths(12))
                ->groupBy(DB::raw("DATE_FORMAT(fecha, '%Y-%m')"))
                ->orderBy('periodo', 'desc')
                ->get();
        }

        return view('reportes.ventas-periodo', compact('datos', 'periodo'));
    }

    public function productosMasVendidos(Request $request): View
    {
        $limit = $request->get('limit', 10);
        $fechaDesde = $request->get('fecha_desde', now()->startOfYear()->toDateString());
        $fechaHasta = $request->get('fecha_hasta', now()->toDateString());

        $datos = DB::table('ventas_detalle')
            ->join('productos', 'ventas_detalle.producto_id', '=', 'productos.id')
            ->join('ventas', 'ventas_detalle.venta_id', '=', 'ventas.id')
            ->where('ventas.estado', 'completada')
            ->whereBetween('ventas.fecha', [$fechaDesde, $fechaHasta])
            ->select(
                'productos.nombre',
                'productos.codigo',
                DB::raw('SUM(ventas_detalle.cantidad) as total_vendido'),
                DB::raw('SUM(ventas_detalle.subtotal) as total_facturado')
            )
            ->groupBy('productos.id', 'productos.nombre', 'productos.codigo')
            ->orderBy('total_vendido', 'desc')
            ->limit($limit)
            ->get();

        return view('reportes.productos-vendidos', compact('datos', 'limit', 'fechaDesde', 'fechaHasta'));
    }

    public function mejoresClientes(Request $request): View
    {
        $limit = $request->get('limit', 10);
        $fechaDesde = $request->get('fecha_desde', now()->startOfYear()->toDateString());
        $fechaHasta = $request->get('fecha_hasta', now()->toDateString());

        // clientes vive en Mongo: no se puede hacer join SQL contra ella.
        $datos = Venta::select(
            'cliente_id',
            DB::raw('COUNT(*) as total_compras'),
            DB::raw('SUM(total) as total_gastado')
        )
            ->where('estado', 'completada')
            ->whereBetween('fecha', [$fechaDesde, $fechaHasta])
            ->groupBy('cliente_id')
            ->orderBy('total_gastado', 'desc')
            ->limit($limit)
            ->get();

        $clientes = Cliente::whereIn('_id', $datos->pluck('cliente_id')->filter()->unique()->values())
            ->get(['nombre', 'apellido', 'email'])
            ->keyBy(fn ($c) => (string) $c->id);

        $datos = $datos->map(function ($fila) use ($clientes) {
            $cliente = $clientes->get($fila->cliente_id);
            $fila->nombre = $cliente->nombre ?? '—';
            $fila->apellido = $cliente->apellido ?? '';
            $fila->email = $cliente->email ?? '';

            return $fila;
        });

        return view('reportes.mejores-clientes', compact('datos', 'limit', 'fechaDesde', 'fechaHasta'));
    }

    public function stockCritico(): View
    {
        $criticos = Producto::where('estado', 'activo')
            ->whereColumn('stock', '<=', 'stock_minimo')
            ->where('stock', '>', 0)
            ->with('categoria')
            ->orderBy('stock', 'asc')
            ->get();

        $agotados = Producto::where('estado', 'activo')
            ->where('stock', 0)
            ->with('categoria')
            ->orderBy('nombre')
            ->get();

        return view('reportes.stock-critico', compact('criticos', 'agotados'));
    }

    public function ganancias(Request $request): View
    {
        $fechaDesde = $request->get('fecha_desde', now()->startOfMonth()->toDateString());
        $fechaHasta = $request->get('fecha_hasta', now()->toDateString());

        // categorias vive en Mongo: no se puede hacer leftJoin SQL contra ella.
        // Se trae el nombre por producto y se resuelve el nombre de categoria aparte.
        $productos = DB::table('ventas_detalle')
            ->join('ventas', 'ventas_detalle.venta_id', '=', 'ventas.id')
            ->join('productos', 'ventas_detalle.producto_id', '=', 'productos.id')
            ->where('ventas.estado', 'completada')
            ->whereBetween('ventas.fecha', [$fechaDesde, $fechaHasta])
            ->select(
                'productos.codigo',
                'productos.nombre',
                'productos.categoria_id',
                'productos.precio_compra',
                'productos.precio_venta',
                DB::raw('SUM(ventas_detalle.cantidad) as unidades_vendidas'),
                DB::raw('SUM(ventas_detalle.subtotal) as total_facturado'),
                DB::raw('SUM(ventas_detalle.cantidad * productos.precio_compra) as costo_total'),
                DB::raw('SUM(ventas_detalle.subtotal) - SUM(ventas_detalle.cantidad * productos.precio_compra) as ganancia_total'),
                DB::raw('CASE WHEN SUM(ventas_detalle.subtotal) > 0 THEN ((SUM(ventas_detalle.subtotal) - SUM(ventas_detalle.cantidad * productos.precio_compra)) / SUM(ventas_detalle.subtotal)) * 100 ELSE 0 END as margen_porcentaje')
            )
            ->groupBy('productos.id', 'productos.codigo', 'productos.nombre', 'productos.categoria_id', 'productos.precio_compra', 'productos.precio_venta')
            ->orderBy('ganancia_total', 'desc')
            ->get();

        $nombresCategoria = Categoria::whereIn('_id', $productos->pluck('categoria_id')->filter()->unique()->values())
            ->pluck('nombre', '_id');

        $productos = $productos->map(function ($fila) use ($nombresCategoria) {
            $fila->categoria = $nombresCategoria->get($fila->categoria_id, '—');

            return $fila;
        });

        $totales = [
            'facturado' => $productos->sum('total_facturado'),
            'costo' => $productos->sum('costo_total'),
            'ganancia' => $productos->sum('ganancia_total'),
            'unidades' => $productos->sum('unidades_vendidas'),
            'margen' => $productos->sum('facturado') > 0 ? ($productos->sum('ganancia_total') / $productos->sum('facturado')) * 100 : 0,
        ];

        $porCategoria = $productos->groupBy('categoria')->map(function ($items) {
            return [
                'facturado' => $items->sum('total_facturado'),
                'costo' => $items->sum('costo_total'),
                'ganancia' => $items->sum('ganancia_total'),
                'unidades' => $items->sum('unidades_vendidas'),
            ];
        })->sortByDesc('ganancia');

        return view('reportes.ganancias', compact('productos', 'totales', 'porCategoria', 'fechaDesde', 'fechaHasta'));
    }

    public function comprasPeriodo(Request $request): View
    {
        $periodo = $request->get('periodo', 'diario');
        $hoy = Carbon::now();
        $compras = Compra::with('proveedor');

        if ($periodo === 'diario') {
            $datos = $compras->select(
                DB::raw('DATE(fecha) as periodo'),
                DB::raw('COUNT(*) as cantidad'),
                DB::raw('SUM(total) as total')
            )
                ->where('estado', 'completada')
                ->where('fecha', '>=', $hoy->copy()->subDays(30))
                ->groupBy(DB::raw('DATE(fecha)'))
                ->orderBy('periodo', 'desc')
                ->get();
        } elseif ($periodo === 'semanal') {
            $datos = $compras->select(
                DB::raw('YEARWEEK(fecha, 1) as semana'),
                DB::raw('MIN(fecha) as periodo'),
                DB::raw('COUNT(*) as cantidad'),
                DB::raw('SUM(total) as total')
            )
                ->where('estado', 'completada')
                ->where('fecha', '>=', $hoy->copy()->subWeeks(12))
                ->groupBy(DB::raw('YEARWEEK(fecha, 1)'))
                ->orderBy('semana', 'desc')
                ->get();
        } else {
            $datos = $compras->select(
                DB::raw("DATE_FORMAT(fecha, '%Y-%m') as periodo"),
                DB::raw('COUNT(*) as cantidad'),
                DB::raw('SUM(total) as total')
            )
                ->where('estado', 'completada')
                ->where('fecha', '>=', $hoy->copy()->subMonths(12))
                ->groupBy(DB::raw("DATE_FORMAT(fecha, '%Y-%m')"))
                ->orderBy('periodo', 'desc')
                ->get();
        }

        return view('reportes.compras-periodo', compact('datos', 'periodo'));
    }

    public function proveedoresRanking(Request $request): View
    {
        $limit = $request->get('limit', 10);

        // proveedores vive en Mongo: no se puede hacer join SQL contra ella.
        $datos = DB::table('compras')
            ->where('estado', 'completada')
            ->select(
                'proveedor_id',
                DB::raw('COUNT(*) as total_compras'),
                DB::raw('SUM(total) as total_gastado')
            )
            ->groupBy('proveedor_id')
            ->orderBy('total_gastado', 'desc')
            ->limit($limit)
            ->get();

        $proveedores = Proveedor::whereIn('_id', $datos->pluck('proveedor_id')->filter()->unique()->values())
            ->get(['nombre', 'email', 'telefono'])
            ->keyBy(fn ($p) => (string) $p->id);

        $datos = $datos->map(function ($fila) use ($proveedores) {
            $proveedor = $proveedores->get($fila->proveedor_id);
            $fila->nombre = $proveedor->nombre ?? '—';
            $fila->email = $proveedor->email ?? '';
            $fila->telefono = $proveedor->telefono ?? '';

            return $fila;
        });

        return view('reportes.proveedores-ranking', compact('datos', 'limit'));
    }

    /**
     * Reporte operativo para retail: ventas por hora (para dotación de turnos),
     * ticket promedio, margen bruto y productos sin rotación.
     */
    public function negocio(Request $request): View
    {
        $desde = $request->get('fecha_desde', now()->subDays(30)->toDateString());
        $hasta = $request->get('fecha_hasta', now()->toDateString());

        $ventas = Venta::where('estado', 'completada')
            ->whereBetween('fecha', [$desde, $hasta])
            ->get(['id', 'total_final', 'created_at']);

        $porHora = array_fill(0, 24, ['cantidad' => 0, 'total' => 0.0]);
        foreach ($ventas as $v) {
            $h = (int) $v->created_at->format('G');
            $porHora[$h]['cantidad']++;
            $porHora[$h]['total'] += (float) $v->total_final;
        }
        $maxHora = max(1, max(array_column($porHora, 'total')));

        $cantidad = $ventas->count();
        $facturado = (float) $ventas->sum('total_final');
        $ticketPromedio = $cantidad ? round($facturado / $cantidad, 2) : 0;

        $margen = DB::table('ventas_detalle')
            ->join('ventas', 'ventas_detalle.venta_id', '=', 'ventas.id')
            ->where('ventas.estado', 'completada')
            ->whereBetween('ventas.fecha', [$desde, $hasta])
            ->selectRaw('SUM(ventas_detalle.subtotal) as venta, SUM(ventas_detalle.cantidad * ventas_detalle.costo_unitario) as costo')
            ->first();
        $margenBruto = $margen && $margen->venta > 0
            ? round(($margen->venta - $margen->costo) / $margen->venta * 100, 1)
            : 0;

        $vendidos = DB::table('ventas_detalle')
            ->join('ventas', 'ventas_detalle.venta_id', '=', 'ventas.id')
            ->where('ventas.estado', 'completada')
            ->where('ventas.fecha', '>=', now()->subDays(30)->toDateString())
            ->pluck('ventas_detalle.producto_id')->unique();

        $sinVenta = Producto::where('estado', 'activo')
            ->whereNotIn('id', $vendidos)
            ->where('stock', '>', 0)
            ->orderByDesc('stock')
            ->limit(50)
            ->get(['id', 'codigo', 'nombre', 'stock', 'precio_venta']);

        return view('reportes.negocio', compact(
            'porHora', 'maxHora', 'cantidad', 'facturado', 'ticketPromedio', 'margenBruto', 'sinVenta', 'desde', 'hasta'
        ));
    }
}
