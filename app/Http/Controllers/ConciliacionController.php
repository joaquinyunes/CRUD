<?php

namespace App\Http\Controllers;

use App\Models\PagoPasarela;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConciliacionController extends Controller
{
    public function index(Request $request): View
    {
        $desde = $request->get('fecha_desde', now()->startOfMonth()->toDateString());
        $hasta = $request->get('fecha_hasta', now()->toDateString());

        $pagos = PagoPasarela::with('venta')
            ->whereBetween('created_at', [$desde.' 00:00:00', $hasta.' 23:59:59'])
            ->orderByDesc('id')
            ->paginate(40)->withQueryString();

        $aprobados = PagoPasarela::whereBetween('created_at', [$desde.' 00:00:00', $hasta.' 23:59:59'])
            ->where('estado', 'aprobado')->get();

        $totales = [
            'bruto' => $aprobados->sum('monto'),
            'comision' => $aprobados->sum('comision'),
            'neto' => $aprobados->sum(fn ($p) => $p->neto_acreditado ?? $p->monto),
            'sin_venta' => $aprobados->whereNull('venta_id')->count(),
        ];

        return view('conciliacion.index', compact('pagos', 'totales', 'desde', 'hasta'));
    }
}
