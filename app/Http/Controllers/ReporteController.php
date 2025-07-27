<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Producto;
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

        $comprasMes = \App\Models\Compra::whereMonth('fecha', $hoy->month)
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

        $totalProveedores = \App\Models\Proveedor::whereHas('compras', function ($q) {
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

        $datos = Venta::select(
                'clientes.nombre',
                'clientes.apellido',
                'clientes.email',
                DB::raw('COUNT(*) as total_compras'),
                DB::raw('SUM(ventas.total) as total_gastado')
            )
            ->join('clientes', 'ventas.cliente_id', '=', 'clientes.id')
            ->where('ventas.estado', 'completada')
            ->whereBetween('ventas.fecha', [$fechaDesde, $fechaHasta])
            ->groupBy('clientes.id', 'clientes.nombre', 'clientes.apellido', 'clientes.email')
            ->orderBy('total_gastado', 'desc')
            ->limit($limit)
            ->get();

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

        $productos = DB::table('ventas_detalle')
            ->join('ventas', 'ventas_detalle.venta_id', '=', 'ventas.id')
            ->join('productos', 'ventas_detalle.producto_id', '=', 'productos.id')
            ->leftJoin('categorias', 'productos.categoria_id', '=', 'categorias.id')
            ->where('ventas.estado', 'completada')
            ->whereBetween('ventas.fecha', [$fechaDesde, $fechaHasta])
            ->select(
                'productos.codigo',
                'productos.nombre',
                'categorias.nombre as categoria',
                'productos.precio_compra',
                'productos.precio_venta',
                DB::raw('SUM(ventas_detalle.cantidad) as unidades_vendidas'),
                DB::raw('SUM(ventas_detalle.subtotal) as total_facturado'),
                DB::raw('SUM(ventas_detalle.cantidad * productos.precio_compra) as costo_total'),
                DB::raw('SUM(ventas_detalle.subtotal) - SUM(ventas_detalle.cantidad * productos.precio_compra) as ganancia_total'),
                DB::raw('CASE WHEN SUM(ventas_detalle.subtotal) > 0 THEN ((SUM(ventas_detalle.subtotal) - SUM(ventas_detalle.cantidad * productos.precio_compra)) / SUM(ventas_detalle.subtotal)) * 100 ELSE 0 END as margen_porcentaje')
            )
            ->groupBy('productos.id', 'productos.codigo', 'productos.nombre', 'productos.precio_compra', 'productos.precio_venta', 'categorias.nombre')
            ->orderBy('ganancia_total', 'desc')
            ->get();

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
        $compras = \App\Models\Compra::with('proveedor');

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

        $datos = DB::table('compras')
            ->join('proveedores', 'compras.proveedor_id', '=', 'proveedores.id')
            ->where('compras.estado', 'completada')
            ->select(
                'proveedores.nombre',
                'proveedores.email',
                'proveedores.telefono',
                DB::raw('COUNT(*) as total_compras'),
                DB::raw('SUM(compras.total) as total_gastado')
            )
            ->groupBy('proveedores.id', 'proveedores.nombre', 'proveedores.email', 'proveedores.telefono')
            ->orderBy('total_gastado', 'desc')
            ->limit($limit)
            ->get();

        return view('reportes.proveedores-ranking', compact('datos', 'limit'));
    }
}
