<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Services\ReportesService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private ReportesService $reportes) {}

    public function index(Request $request)
    {
        // Las cifras del negocio son de quien tiene el permiso, no de quien inició sesión.
        if (! $request->user()->role?->tienePermiso('dashboard.ver')) {
            return view('dashboard-restringido');
        }

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

        // Ganancia = margen bruto sobre lo efectivamente vendido (facturado menos
        // el costo de esa mercaderia), NO ventas menos compras: reponer stock no
        // es una perdida. Es la misma formula que usa el reporte de rentabilidad.
        $gananciaMes = $this->reportes->gananciaBruta(now()->year, now()->month);
        $margenMes = $ventasMes > 0 ? ($gananciaMes / $ventasMes) * 100 : 0;

        $ventasMesAnterior = Venta::where('estado', 'completada')
            ->whereMonth('fecha', now()->subMonth()->month)
            ->whereYear('fecha', now()->subMonth()->year)
            ->sum('total');
        $comprasMesAnterior = Compra::where('estado', 'completada')
            ->whereMonth('fecha', now()->subMonth()->month)
            ->whereYear('fecha', now()->subMonth()->year)
            ->sum('total');
        $gananciaMesAnterior = $this->reportes->gananciaBruta(
            now()->subMonth()->year,
            now()->subMonth()->month
        );

        $variacionVentas = $ventasMesAnterior > 0 ? (($ventasMes - $ventasMesAnterior) / $ventasMesAnterior) * 100 : 0;
        $variacionGanancia = $gananciaMesAnterior != 0 ? (($gananciaMes - $gananciaMesAnterior) / abs($gananciaMesAnterior)) * 100 : 0;

        $totalVentasMes = Venta::where('estado', 'completada')
            ->whereMonth('fecha', now()->month)
            ->whereYear('fecha', now()->year)
            ->count();
        $ticketPromedio = $totalVentasMes > 0 ? $ventasMes / $totalVentasMes : 0;

        $topRentables = $this->reportes->topRentables(now()->year, now()->month, 5);

        $chartVentasDiarias = $this->reportes->ventasDiarias($fechaDesde, $fechaHasta);

        $chartTopProductos = $this->reportes->topProductosPorCantidad($fechaDesde, $fechaHasta, 5);

        $chartStockBajo = Producto::where('estado', 'activo')
            ->whereColumn('stock', '<=', 'stock_minimo')
            ->orderBy('stock')
            ->limit(5)
            ->get(['nombre', 'stock', 'stock_minimo']);

        $chartMovimientosMes = $this->reportes->movimientosDelMes(now()->year, now()->month);

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
