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

        return view('reportes.index', compact(
            'ventasHoy',
            'ventasMes',
            'totalVentasMes',
            'productosStockCritico',
            'productosAgotados'
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

        $datos = DB::table('ventas_detalle')
            ->join('productos', 'ventas_detalle.producto_id', '=', 'productos.id')
            ->join('ventas', 'ventas_detalle.venta_id', '=', 'ventas.id')
            ->where('ventas.estado', 'completada')
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

        return view('reportes.productos-vendidos', compact('datos', 'limit'));
    }

    public function mejoresClientes(Request $request): View
    {
        $limit = $request->get('limit', 10);

        $datos = Venta::select(
                'clientes.nombre',
                'clientes.apellido',
                'clientes.email',
                DB::raw('COUNT(*) as total_compras'),
                DB::raw('SUM(ventas.total) as total_gastado')
            )
            ->join('clientes', 'ventas.cliente_id', '=', 'clientes.id')
            ->where('ventas.estado', 'completada')
            ->groupBy('clientes.id', 'clientes.nombre', 'clientes.apellido', 'clientes.email')
            ->orderBy('total_gastado', 'desc')
            ->limit($limit)
            ->get();

        return view('reportes.mejores-clientes', compact('datos', 'limit'));
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
}
