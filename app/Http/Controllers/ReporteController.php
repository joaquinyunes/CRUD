<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Producto;
use App\Models\Venta;
use App\Services\ReportesService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ReporteController extends Controller
{
    public function __construct(private ReportesService $reportes) {}

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

        $totalProveedores = $this->reportes->proveedoresConCompras();

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
        $datos = $this->reportes->porPeriodo('ventas', $periodo, Carbon::now());

        return view('reportes.ventas-periodo', compact('datos', 'periodo'));
    }

    public function productosMasVendidos(Request $request): View
    {
        $limit = $request->get('limit', 10);
        $fechaDesde = $request->get('fecha_desde', now()->startOfYear()->toDateString());
        $fechaHasta = $request->get('fecha_hasta', now()->toDateString());

        $datos = $this->reportes->productosMasVendidos($fechaDesde, $fechaHasta, (int) $limit);

        return view('reportes.productos-vendidos', compact('datos', 'limit', 'fechaDesde', 'fechaHasta'));
    }

    public function mejoresClientes(Request $request): View
    {
        $limit = $request->get('limit', 10);
        $fechaDesde = $request->get('fecha_desde', now()->startOfYear()->toDateString());
        $fechaHasta = $request->get('fecha_hasta', now()->toDateString());

        $datos = $this->reportes->mejoresClientes($fechaDesde, $fechaHasta, (int) $limit);

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

        $productos = $this->reportes->rentabilidadPorProducto($fechaDesde, $fechaHasta);

        $totales = [
            'facturado' => $productos->sum('total_facturado'),
            'costo'     => $productos->sum('costo_total'),
            'ganancia'  => $productos->sum('ganancia_total'),
            'unidades'  => $productos->sum('unidades_vendidas'),
            'margen'    => $productos->sum('facturado') > 0 ? ($productos->sum('ganancia_total') / $productos->sum('facturado')) * 100 : 0,
        ];

        $porCategoria = $productos->groupBy('categoria')->map(function ($items) {
            return [
                'facturado' => $items->sum('total_facturado'),
                'costo'     => $items->sum('costo_total'),
                'ganancia'  => $items->sum('ganancia_total'),
                'unidades'  => $items->sum('unidades_vendidas'),
            ];
        })->sortByDesc('ganancia');

        return view('reportes.ganancias', compact('productos', 'totales', 'porCategoria', 'fechaDesde', 'fechaHasta'));
    }

    public function comprasPeriodo(Request $request): View
    {
        $periodo = $request->get('periodo', 'diario');
        $datos = $this->reportes->porPeriodo('compras', $periodo, Carbon::now());

        return view('reportes.compras-periodo', compact('datos', 'periodo'));
    }

    public function proveedoresRanking(Request $request): View
    {
        $limit = $request->get('limit', 10);

        $datos = $this->reportes->proveedoresRanking((int) $limit);

        return view('reportes.proveedores-ranking', compact('datos', 'limit'));
    }
}
