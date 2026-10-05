<?php

namespace App\Http\Controllers;

use App\Exceptions\StockInsuficienteException;
use App\Http\Requests\CompraRequest;
use App\Models\Compra;
use App\Models\Deposito;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Services\CompraService;
use App\Services\StockDocumentoService;
use App\Support\Orden;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CompraController extends Controller
{
    public function __construct(
        private StockDocumentoService $stockDoc,
        private CompraService $compras,
    ) {}

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

        $compras = Orden::aplicar($query, [
            'numero'    => 'numero',
            'proveedor' => Proveedor::select('nombre')->whereColumn('proveedores.id', 'compras.proveedor_id'),
            'fecha'     => fn ($q, $dir) => $q->orderBy('fecha', $dir)->orderBy('id', $dir),
            'total'     => 'total_final',
            'estado'    => 'estado',
        ], 'fecha', 'desc')
            ->paginate(20)
            ->withQueryString();

        return view('compras.index', compact('compras'));
    }

    public function create(): View
    {
        $proveedores = Proveedor::activos()->orderBy('nombre')->get();
        $productos = Producto::where('estado', 'activo')->orderBy('nombre')->get();
        $metodosPago = MetodoPago::activos()->get();
        $depositos = Deposito::activos()->orderByDesc('es_principal')->orderBy('nombre')->get();

        return view('compras.form', compact('proveedores', 'productos', 'metodosPago', 'depositos'));
    }

    public function store(CompraRequest $request): RedirectResponse
    {
        try {
            $this->compras->crear($request->validated());
        } catch (StockInsuficienteException $e) {
            return back()->withErrors(['detalles' => $e->getMessage()])->withInput();
        }

        return redirect()->route('compras.index')->with('success', 'Compra registrada correctamente.');
    }

    public function edit(Compra $compra): View
    {
        $this->authorize('update', $compra);
        abort_if($compra->estado === 'anulada', 404);
        $compra->load(['detalles.producto', 'pagos.metodoPago']);

        // El proveedor de la compra sigue en la lista aunque lo hayan archivado.
        $proveedores = Proveedor::activos()->orWhere('id', $compra->proveedor_id)
            ->orderBy('nombre')->get();
        $productos = Producto::where('estado', 'activo')->orderBy('nombre')->get();
        $metodosPago = MetodoPago::activos()->get();
        $depositos = Deposito::activos()->orderByDesc('es_principal')->orderBy('nombre')->get();

        return view('compras.form', compact('compra', 'proveedores', 'productos', 'metodosPago', 'depositos'));
    }

    public function update(CompraRequest $request, Compra $compra): RedirectResponse
    {
        $this->authorize('update', $compra);
        abort_if($compra->estado === 'anulada', 404);

        try {
            $this->compras->actualizar($compra, $request->validated());
        } catch (StockInsuficienteException $e) {
            return back()->withErrors(['detalles' => $e->getMessage()])->withInput();
        }

        return redirect()->route('compras.index')->with('success', 'Compra actualizada correctamente.');
    }

    public function destroy(Request $request, Compra $compra): RedirectResponse
    {
        $this->authorize('delete', $compra);

        if ($compra->estado === 'anulada') {
            return redirect()->route('compras.index')->with('success', 'La compra ya estaba anulada.');
        }

        try {
            DB::transaction(function () use ($request, $compra) {
                $this->stockDoc->revertirCompra($compra);
                $compra->update([
                    'estado'           => 'anulada',
                    'motivo_anulacion' => $request->input('motivo', 'Anulada por el usuario'),
                ]);
            });
        } catch (StockInsuficienteException $e) {
            return back()->withErrors(['general' => 'No se puede anular: '.$e->getMessage()]);
        }

        return redirect()->route('compras.index')->with('success', 'Compra anulada correctamente.');
    }

    public function show(Compra $compra): View
    {
        $compra->load(['detalles.producto', 'proveedor', 'user']);

        return view('compras.show', compact('compra'));
    }
}
