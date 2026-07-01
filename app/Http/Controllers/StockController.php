<?php

namespace App\Http\Controllers;

use App\Models\MovimientoStock;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockController extends Controller
{
    public function index(Request $request): View
    {
        $query = MovimientoStock::with('producto', 'user');

        if ($request->filled('producto_id')) {
            $query->paraProducto($request->producto_id);
        }

        if ($request->filled('tipo')) {
            $query->paraTipo($request->tipo);
        }

        if ($request->filled('fecha_desde') || $request->filled('fecha_hasta')) {
            $query->paraFecha($request->fecha_desde, $request->fecha_hasta);
        }

        $movimientos = $query->orderBy('created_at', 'desc')
                             ->orderBy('id', 'desc')
                             ->paginate(20)
                             ->withQueryString();

        $productos = Producto::where('estado', 'activo')->orderBy('nombre')->get();

        return view('stock.index', compact('movimientos', 'productos'));
    }
}
