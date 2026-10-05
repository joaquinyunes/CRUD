<?php

namespace App\Http\Controllers;

use App\Exceptions\ReglaDeNegocioException;
use App\Exceptions\StockInsuficienteException;
use App\Http\Requests\VentaRequest;
use App\Models\Cliente;
use App\Models\Deposito;
use App\Models\MetodoPago;
use App\Models\User;
use App\Models\Venta;
use App\Services\StockDocumentoService;
use App\Services\VentaService;
use App\Support\Orden;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class VentaController extends Controller
{
    public function __construct(
        private StockDocumentoService $stockDoc,
        private VentaService $ventas,
    ) {}

    public function index(Request $request): View
    {
        $query = Venta::with('cliente', 'user', 'pagos.metodoPago');

        if ($request->filled('buscar')) {
            $query->buscar($request->buscar);
        }

        if ($request->filled('fecha_desde') || $request->filled('fecha_hasta')) {
            $query->paraFecha($request->fecha_desde, $request->fecha_hasta);
        }

        if ($request->filled('estado')) {
            $query->paraEstado($request->estado);
        }

        $ventas = Orden::aplicar($query, [
            'numero'   => 'numero',
            'cliente'  => Cliente::select('nombre')->whereColumn('clientes.id', 'ventas.cliente_id'),
            'fecha'    => fn ($q, $dir) => $q->orderBy('fecha', $dir)->orderBy('id', $dir),
            'total'    => 'total_final',
            'estado'   => 'estado',
            'vendedor' => User::select('name')->whereColumn('users.id', 'ventas.user_id'),
        ], 'fecha', 'desc')
            ->paginate(20)
            ->withQueryString();

        return view('ventas.index', compact('ventas'));
    }

    /**
     * El formulario ya no trae el catálogo embebido: clientes y productos se
     * buscan contra sus endpoints a medida que se escribe. Con los datos de
     * demostración no se notaba; con un catálogo real la pantalla tardaba
     * segundos en abrir y el HTML pesaba megas.
     */
    public function create(): View
    {
        return view('ventas.form', [
            'clienteElegido' => null,
            'metodosPago'    => MetodoPago::activos()->get(),
            'depositos'      => Deposito::activos()->orderByDesc('es_principal')->orderBy('nombre')->get(),
        ]);
    }

    public function store(VentaRequest $request): RedirectResponse
    {
        try {
            $this->ventas->crear($request->validated());
        } catch (ReglaDeNegocioException $e) {
            return back()->withErrors([$e->campo => $e->getMessage()])->withInput();
        } catch (StockInsuficienteException $e) {
            return back()->withErrors(['detalles' => $e->getMessage()])->withInput();
        }

        return redirect()->route('ventas.index')->with('success', 'Venta registrada correctamente.');
    }

    public function edit(Venta $venta): View
    {
        $this->authorize('update', $venta);
        abort_if($venta->estado === 'anulada', 404);
        $venta->load(['detalles.producto', 'pagos.metodoPago', 'cliente']);

        return view('ventas.form', [
            'venta'          => $venta,
            'clienteElegido' => $venta->cliente,
            'metodosPago'    => MetodoPago::activos()->get(),
            'depositos'      => Deposito::activos()->orderByDesc('es_principal')->orderBy('nombre')->get(),
        ]);
    }

    public function update(VentaRequest $request, Venta $venta): RedirectResponse
    {
        $this->authorize('update', $venta);
        abort_if($venta->estado === 'anulada', 404);

        try {
            $this->ventas->actualizar($venta, $request->validated());
        } catch (ReglaDeNegocioException $e) {
            return back()->withErrors([$e->campo => $e->getMessage()])->withInput();
        } catch (StockInsuficienteException $e) {
            return back()->withErrors(['detalles' => $e->getMessage()])->withInput();
        }

        return redirect()->route('ventas.index')->with('success', 'Venta actualizada correctamente.');
    }

    /**
     * No se elimina físicamente: se anula devolviendo el stock y conservando el historial.
     */
    public function destroy(Request $request, Venta $venta): RedirectResponse
    {
        $this->authorize('delete', $venta);

        if ($venta->estado === 'anulada') {
            return redirect()->route('ventas.index')->with('success', 'La venta ya estaba anulada.');
        }

        DB::transaction(function () use ($request, $venta) {
            $this->stockDoc->revertirVenta($venta);
            $venta->update([
                'estado'           => 'anulada',
                'motivo_anulacion' => $request->input('motivo', 'Anulada por el usuario'),
            ]);
        });

        return redirect()->route('ventas.index')->with('success', 'Venta anulada correctamente. El stock fue devuelto.');
    }

    public function show(Venta $venta): View
    {
        $venta->load(['detalles.producto', 'cliente', 'user', 'pagos.metodoPago']);

        return view('ventas.show', compact('venta'));
    }
}
