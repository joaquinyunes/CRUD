<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\Producto;
use App\Models\Proveedor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CompraController extends Controller
{
    public function index(Request $request): View
    {
        $query = Compra::with('proveedor', 'user');

        if ($request->filled('buscar')) {
            $query->buscar($request->buscar);
        }

        if ($request->filled('fecha_desde') || $request->filled('fecha_hasta')) {
            $query->paraFecha($request->fecha_desde, $request->fecha_hasta);
        }

        if ($request->filled('estado')) {
            $query->paraEstado($request->estado);
        }

        $compras = $query->orderBy('fecha', 'desc')
                         ->orderBy('id', 'desc')
                         ->paginate(20)
                         ->withQueryString();

        return view('compras.index', compact('compras'));
    }

    public function create(): View
    {
        $proveedores = Proveedor::orderBy('nombre')->get();
        $productos = Producto::where('estado', 'activo')->orderBy('nombre')->get();

        return view('compras.form', compact('proveedores', 'productos'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'proveedor_id'  => ['required', 'exists:proveedores,id'],
            'fecha'         => ['required', 'date'],
            'estado'        => ['required', 'in:pendiente,completada,cancelada'],
            'detalles'      => ['required', 'array', 'min:1'],
            'detalles.*.producto_id' => ['required', 'exists:productos,id'],
            'detalles.*.cantidad'    => ['required', 'integer', 'min:1'],
            'detalles.*.precio'      => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($request) {
            $numero = $this->generarNumero();

            $detalles = collect($request->detalles)->map(function ($item) {
                $subtotal = $item['cantidad'] * $item['precio'];
                return [
                    'producto_id' => $item['producto_id'],
                    'cantidad'    => $item['cantidad'],
                    'precio'      => $item['precio'],
                    'subtotal'    => $subtotal,
                ];
            });

            $total = $detalles->sum('subtotal');

            $compra = Compra::create([
                'numero'      => $numero,
                'proveedor_id' => $request->proveedor_id,
                'fecha'       => $request->fecha,
                'total'       => $total,
                'estado'      => $request->estado,
                'user_id'     => auth()->id(),
            ]);

            foreach ($detalles as $detalle) {
                $compra->detalles()->create($detalle);
            }
        });

        return redirect()->route('compras.index')
                         ->with('success', 'Compra registrada correctamente.');
    }

    public function edit(Compra $compra): View
    {
        $compra->load('detalles.producto');

        $proveedores = Proveedor::orderBy('nombre')->get();
        $productos = Producto::where('estado', 'activo')->orderBy('nombre')->get();

        return view('compras.form', compact('compra', 'proveedores', 'productos'));
    }

    public function update(Request $request, Compra $compra): RedirectResponse
    {
        $request->validate([
            'proveedor_id'  => ['required', 'exists:proveedores,id'],
            'fecha'         => ['required', 'date'],
            'estado'        => ['required', 'in:pendiente,completada,cancelada'],
            'detalles'      => ['required', 'array', 'min:1'],
            'detalles.*.producto_id' => ['required', 'exists:productos,id'],
            'detalles.*.cantidad'    => ['required', 'integer', 'min:1'],
            'detalles.*.precio'      => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($request, $compra) {
            $detalles = collect($request->detalles)->map(function ($item) {
                $subtotal = $item['cantidad'] * $item['precio'];
                return [
                    'producto_id' => $item['producto_id'],
                    'cantidad'    => $item['cantidad'],
                    'precio'      => $item['precio'],
                    'subtotal'    => $subtotal,
                ];
            });

            $total = $detalles->sum('subtotal');

            $compra->update([
                'proveedor_id' => $request->proveedor_id,
                'fecha'        => $request->fecha,
                'total'        => $total,
                'estado'       => $request->estado,
            ]);

            $compra->detalles()->delete();

            foreach ($detalles as $detalle) {
                $compra->detalles()->create($detalle);
            }
        });

        return redirect()->route('compras.index')
                         ->with('success', 'Compra actualizada correctamente.');
    }

    public function destroy(Compra $compra): RedirectResponse
    {
        $compra->delete();

        return redirect()->route('compras.index')
                         ->with('success', 'Compra eliminada correctamente.');
    }

    public function show(Compra $compra): View
    {
        $compra->load(['detalles.producto', 'proveedor', 'user']);

        return view('compras.show', compact('compra'));
    }

    private function generarNumero(): string
    {
        $ultima = Compra::where('numero', 'like', 'COM-%')
                        ->orderByRaw("CAST(SUBSTRING(numero, 5) AS UNSIGNED) DESC")
                        ->first();

        if ($ultima) {
            $ultimoNumero = (int) substr($ultima->numero, 4);
            $nuevoNumero = $ultimoNumero + 1;
        } else {
            $nuevoNumero = 1;
        }

        return 'COM-' . str_pad($nuevoNumero, 5, '0', STR_PAD_LEFT);
    }
}
