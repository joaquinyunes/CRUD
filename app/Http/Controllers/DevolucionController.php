<?php

namespace App\Http\Controllers;

use App\Exceptions\ReglaDeNegocioException;
use App\Exceptions\StockInsuficienteException;
use App\Http\Requests\DevolucionRequest;
use App\Models\Compra;
use App\Models\Devolucion;
use App\Models\Venta;
use App\Services\DevolucionService;
use App\Support\Orden;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DevolucionController extends Controller
{
    public function __construct(private DevolucionService $devoluciones) {}

    public function index(Request $request): View
    {
        $query = Devolucion::with(['venta.cliente', 'compra.proveedor', 'user'])
            ->visiblesPara($request->user())
            ->when($request->filled('tipo'), fn ($q) => $q->where('tipo', $request->tipo));

        $devoluciones = Orden::aplicar($query, [
            'numero' => 'numero',
            'tipo'   => 'tipo',
            'fecha'  => fn ($q, $dir) => $q->orderBy('fecha', $dir)->orderBy('id', $dir),
            'total'  => 'total',
            'estado' => 'estado',
        ], 'fecha', 'desc')
            ->paginate(20)->withQueryString();

        return view('devoluciones.index', compact('devoluciones'));
    }

    public function show(Request $request, Devolucion $devolucion): View
    {
        abort_unless(Devolucion::visiblesPara($request->user())->where('_id', $devolucion->id)->exists(), 403);

        $devolucion->load(['detalles.producto', 'venta.cliente', 'compra.proveedor', 'user']);

        return view('devoluciones.show', compact('devolucion'));
    }

    public function createVenta(Venta $venta): View
    {
        $this->authorize('view', $venta);
        abort_unless($venta->estado === 'completada' && $venta->stock_aplicado, 404);
        $venta->load('detalles.producto');
        $devueltos = $this->devoluciones->devueltosPorProducto('venta', $venta->id);

        return view('devoluciones.form-venta', compact('venta', 'devueltos'));
    }

    public function storeVenta(DevolucionRequest $request, Venta $venta): RedirectResponse
    {
        $this->authorize('view', $venta);
        abort_unless($venta->estado === 'completada' && $venta->stock_aplicado, 404);

        // M4: devolver plata de la caja es una decisión de quien supervisa, no de cualquiera.
        if ($request->boolean('reembolso_efectivo') && ! $request->user()->role?->tienePermiso('devoluciones.reembolsar')) {
            return back()->withErrors(['reembolso_efectivo' => 'No tenés permiso para reembolsar en efectivo.'])->withInput();
        }

        try {
            $this->devoluciones->devolverVenta($venta, $request->detalles, $request->motivo, $request->boolean('reembolso_efectivo'), auth()->id());
        } catch (ReglaDeNegocioException $e) {
            return back()->withErrors([$e->campo => $e->getMessage()])->withInput();
        } catch (StockInsuficienteException $e) {
            return back()->withErrors(['detalles' => $e->getMessage()])->withInput();
        }

        return redirect()->route('ventas.show', $venta)->with('success', 'Devolución registrada. El stock fue reingresado.');
    }

    public function createCompra(Compra $compra): View
    {
        $this->authorize('view', $compra);
        abort_unless($compra->estado === 'completada' && $compra->stock_aplicado, 404);
        $compra->load('detalles.producto');
        $devueltos = $this->devoluciones->devueltosPorProducto('compra', $compra->id);

        return view('devoluciones.form-compra', compact('compra', 'devueltos'));
    }

    public function storeCompra(DevolucionRequest $request, Compra $compra): RedirectResponse
    {
        $this->authorize('view', $compra);
        abort_unless($compra->estado === 'completada' && $compra->stock_aplicado, 404);

        try {
            $this->devoluciones->devolverCompra($compra, $request->detalles, $request->motivo, auth()->id());
        } catch (ReglaDeNegocioException $e) {
            return back()->withErrors([$e->campo => $e->getMessage()])->withInput();
        } catch (StockInsuficienteException $e) {
            return back()->withErrors(['detalles' => $e->getMessage()])->withInput();
        }

        return redirect()->route('compras.show', $compra)->with('success', 'Devolución a proveedor registrada. El stock fue descontado.');
    }
}
