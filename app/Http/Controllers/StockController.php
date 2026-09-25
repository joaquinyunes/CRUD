<?php

namespace App\Http\Controllers;

use App\Models\MovimientoStock;
use App\Models\Producto;
use App\Models\User;
use App\Support\Orden;
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

        $movimientos = Orden::aplicar($query, [
            'fecha'      => fn ($q, $dir) => $q->orderBy('created_at', $dir)->orderBy('id', $dir),
            'producto'   => Producto::select('nombre')->whereColumn('productos.id', 'movimientos_stock.producto_id'),
            'tipo'       => 'tipo',
            'cantidad'   => 'cantidad',
            'referencia' => 'referencia_tipo',
            'usuario'    => User::select('name')->whereColumn('users.id', 'movimientos_stock.user_id'),
        ], 'fecha', 'desc')
            ->paginate(20)
            ->withQueryString();

        $productos = Producto::where('estado', 'activo')->orderBy('nombre')->get();

        return view('stock.index', compact('movimientos', 'productos'));
    }
}
