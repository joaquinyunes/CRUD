<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Compra;
use App\Models\MovimientoStock;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $fechaDesde = $request->fecha_desde ?? now()->subDays(30)->toDateString();
        $fechaHasta = $request->fecha_hasta ?? now()->toDateString();

        $ventasHoy = Venta::whereDate('fecha', now())->where('estado', 'completada')->count();
        $ingresoHoy = Venta::whereDate('fecha', now())->where('estado', 'completada')->sum('total');
        $clientesNuevos = Cliente::whereDate('created_at', now())->count();

        $totalProductos = Producto::where('estado', 'activo')->count();
        $totalUsuarios = User::count();
        $totalClientes = Cliente::where('estado', 'activo')->count();
        $stockCritico = Producto::where('estado', 'activo')
            ->whereColumn('stock', '<=', 'stock_minimo')
            ->count();

        $ventasMes = Venta::where('estado', 'completada')
            ->whereMonth('fecha', now()->month)
            ->whereYear('fecha', now()->year)
            ->sum('total');

        $comprasMes = Compra::where('estado', 'completada')
            ->whereMonth('fecha', now()->month)
            ->whereYear('fecha', now()->year)
            ->sum('total');

        $gananciaMes = $ventasMes - $comprasMes;
        $margenMes = $ventasMes > 0 ? ($gananciaMes / $ventasMes) * 100 : 0;

        $ventasMesAnterior = Venta::where('estado', 'completada')
            ->whereMonth('fecha', now()->subMonth()->month)
            ->whereYear('fecha', now()->subMonth()->year)
            ->sum('total');
        $comprasMesAnterior = Compra::where('estado', 'completada')
            ->whereMonth('fecha', now()->subMonth()->month)
            ->whereYear('fecha', now()->subMonth()->year)
            ->sum('total');
        $gananciaMesAnterior = $ventasMesAnterior - $comprasMesAnterior;

        $variacionVentas = $ventasMesAnterior > 0 ? (($ventasMes - $ventasMesAnterior) / $ventasMesAnterior) * 100 : 0;
        $variacionGanancia = $gananciaMesAnterior != 0 ? (($gananciaMes - $gananciaMesAnterior) / abs($gananciaMesAnterior)) * 100 : 0;

        $totalVentasMes = Venta::where('estado', 'completada')
            ->whereMonth('fecha', now()->month)
            ->whereYear('fecha', now()->year)
            ->count();
        $ticketPromedio = $totalVentasMes > 0 ? $ventasMes / $totalVentasMes : 0;

        $topRentables = DB::table('ventas_detalle')
            ->join('ventas', 'ventas.id', '=', 'ventas_detalle.venta_id')
            ->join('productos', 'productos.id', '=', 'ventas_detalle.producto_id')
            ->where('ventas.estado', 'completada')
            ->whereMonth('ventas.fecha', now()->month)
            ->whereYear('ventas.fecha', now()->year)
            ->select(
                'productos.nombre',
                DB::raw('SUM(ventas_detalle.cantidad) as vendidos'),
                DB::raw('SUM(ventas_detalle.subtotal) - SUM(ventas_detalle.cantidad * productos.precio_compra) as ganancia')
            )
            ->groupBy('productos.id', 'productos.nombre')
            ->orderByDesc('ganancia')
            ->limit(5)
            ->get();

        $chartVentasDiarias = Venta::where('estado', 'completada')
            ->whereBetween('fecha', [$fechaDesde, $fechaHasta])
            ->selectRaw('DATE(fecha) as fecha, SUM(total) as total')
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();

        $chartTopProductos = DB::table('ventas_detalle')
            ->join('ventas', 'ventas.id', '=', 'ventas_detalle.venta_id')
            ->join('productos', 'productos.id', '=', 'ventas_detalle.producto_id')
            ->where('ventas.estado', 'completada')
            ->whereBetween('ventas.fecha', [$fechaDesde, $fechaHasta])
            ->selectRaw('productos.nombre, SUM(ventas_detalle.cantidad) as cantidad')
            ->groupBy('productos.nombre')
            ->orderByDesc('cantidad')
            ->limit(5)
            ->get();

        $chartStockBajo = Producto::where('estado', 'activo')
            ->whereColumn('stock', '<=', 'stock_minimo')
            ->selectRaw('nombre, stock, stock_minimo')
            ->orderBy('stock')
            ->limit(5)
            ->get();

        $chartMovimientosMes = MovimientoStock::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->selectRaw('tipo, COUNT(*) as cantidad')
            ->groupBy('tipo')
            ->get();

        return view('dashboard', compact(
            'ventasHoy',
            'ingresoHoy',
            'clientesNuevos',
            'totalProductos',
            'totalUsuarios',
            'totalClientes',
            'stockCritico',
            'ventasMes',
            'comprasMes',
            'gananciaMes',
            'margenMes',
            'variacionVentas',
            'variacionGanancia',
            'ticketPromedio',
            'totalVentasMes',
            'topRentables',
            'chartVentasDiarias',
            'chartTopProductos',
            'chartStockBajo',
            'chartMovimientosMes',
            'fechaDesde',
            'fechaHasta'
        ));
    }
}
