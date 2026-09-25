<?php

namespace App\Http\Controllers;

use App\Events\VentaCreada;
use App\Exceptions\StockInsuficienteException;
use App\Models\Cliente;
use App\Models\Deposito;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\Setting;
use App\Models\User;
use App\Models\Venta;
use App\Services\CajaService;
use App\Services\StockDocumentoService;
use App\Support\CalculadorTotales;
use App\Support\Orden;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class VentaController extends Controller
{
    public function __construct(
        private StockDocumentoService $stockDoc,
        private CajaService $caja,
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

    public function create(): View
    {
        $clientes = Cliente::where('estado', 'activo')->orderBy('nombre')->get();
        $productos = Producto::where('estado', 'activo')->orderBy('nombre')->get();
        $metodosPago = MetodoPago::activos()->get();
        $depositos = Deposito::activos()->orderByDesc('es_principal')->orderBy('nombre')->get();

        return view('ventas.form', compact('clientes', 'productos', 'metodosPago', 'depositos'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->validar($request);

        $cliente = Cliente::findOrFail($request->cliente_id);
        if ($cliente->estado !== 'activo') {
            return back()->withErrors(['cliente_id' => 'El cliente seleccionado no está activo.'])->withInput();
        }

        $detalles = $this->construirDetalles($request->detalles);
        $totales = CalculadorTotales::calcular(
            $detalles->all(),
            $request->input('descuento_tipo'),
            (float) $request->input('descuento', 0)
        );

        if ($excede = $this->excedeCredito($cliente, $totales['total'], $request->input('metodos_pago', []))) {
            return back()->withErrors(['cliente_id' => $excede])->withInput();
        }

        try {
            $venta = DB::transaction(function () use ($request, $detalles, $totales) {

                $venta = Venta::create([
                    'numero'         => $this->generarNumero(),
                    'cliente_id'     => $request->cliente_id,
                    'deposito_id'    => $request->deposito_id ?: Deposito::principalId(),
                    'fecha'          => $request->fecha,
                    'subtotal'       => $totales['subtotal'],
                    'descuento'      => $totales['descuento'],
                    'descuento_tipo' => $request->input('descuento_tipo'),
                    'impuesto'       => $totales['impuesto'],
                    'total_final'    => $totales['total'],
                    'total'          => $totales['total'],
                    'estado'         => $request->estado,
                    'user_id'        => auth()->id(),
                ]);

                $venta->detalles()->createMany($detalles->all());
                $this->sincronizarPagos($venta, $request->input('metodos_pago', []), $totales['total']);

                if ($venta->estado === 'completada') {
                    $this->stockDoc->aplicarVenta($venta);
                }

                return $venta;
            });
        } catch (StockInsuficienteException $e) {
            return back()->withErrors(['detalles' => $e->getMessage()])->withInput();
        }

        $this->registrarEfectivoEnCaja($venta, $request->input('metodos_pago', []));

        VentaCreada::dispatch($venta);

        return redirect()->route('ventas.index')->with('success', 'Venta registrada correctamente.');
    }

    public function edit(Venta $venta): View
    {
        $this->authorize('update', $venta);
        abort_if($venta->estado === 'anulada', 404);
        $venta->load(['detalles.producto', 'pagos.metodoPago']);

        $clientes = Cliente::where('estado', 'activo')->orderBy('nombre')->get();
        $productos = Producto::where('estado', 'activo')->orderBy('nombre')->get();
        $metodosPago = MetodoPago::activos()->get();
        $depositos = Deposito::activos()->orderByDesc('es_principal')->orderBy('nombre')->get();

        return view('ventas.form', compact('venta', 'clientes', 'productos', 'metodosPago', 'depositos'));
    }

    public function update(Request $request, Venta $venta): RedirectResponse
    {
        $this->authorize('update', $venta);
        abort_if($venta->estado === 'anulada', 404);
        $this->validar($request);

        $cliente = Cliente::findOrFail($request->cliente_id);
        if ($cliente->estado !== 'activo') {
            return back()->withErrors(['cliente_id' => 'El cliente seleccionado no está activo.'])->withInput();
        }

        $efectivoIds = MetodoPago::where('codigo', 'efectivo')->pluck('id')->all();
        $efectivoAnterior = (float) $venta->pagos()->whereIn('metodo_pago_id', $efectivoIds)->sum('monto');

        try {
            DB::transaction(function () use ($request, $venta) {
                // Devuelve el stock del detalle anterior antes de reescribirlo.
                $this->stockDoc->revertirVenta($venta);

                $detalles = $this->construirDetalles($request->detalles);
                $totales = CalculadorTotales::calcular(
                    $detalles->all(),
                    $request->input('descuento_tipo'),
                    (float) $request->input('descuento', 0)
                );

                $venta->update([
                    'cliente_id'     => $request->cliente_id,
                    'deposito_id'    => $request->deposito_id ?: $venta->deposito_id,
                    'fecha'          => $request->fecha,
                    'subtotal'       => $totales['subtotal'],
                    'descuento'      => $totales['descuento'],
                    'descuento_tipo' => $request->input('descuento_tipo'),
                    'impuesto'       => $totales['impuesto'],
                    'total_final'    => $totales['total'],
                    'total'          => $totales['total'],
                    'estado'         => $request->estado,
                ]);

                $venta->detalles()->delete();
                $venta->detalles()->createMany($detalles->all());
                $this->sincronizarPagos($venta, $request->input('metodos_pago', []), $totales['total']);

                if ($venta->estado === 'completada') {
                    $this->stockDoc->aplicarVenta($venta);
                }
            });
        } catch (StockInsuficienteException $e) {
            return back()->withErrors(['detalles' => $e->getMessage()])->withInput();
        }

        $this->ajustarEfectivoEnCaja($venta, $efectivoAnterior, $request->input('metodos_pago', []));

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

    private function validar(Request $request): void
    {
        $request->validate([
            'cliente_id'             => ['required', 'exists:clientes,id'],
            'deposito_id'            => ['nullable', 'exists:depositos,id'],
            'fecha'                  => ['required', 'date'],
            'estado'                 => ['required', 'in:pendiente,completada,cancelada'],
            'detalles'               => ['required', 'array', 'min:1'],
            'detalles.*.producto_id' => ['required', 'exists:productos,id'],
            'detalles.*.cantidad'    => ['required', 'integer', 'min:1'],
            'detalles.*.precio'      => ['required', 'numeric', 'min:0'],
            'descuento'              => ['nullable', 'numeric', 'min:0'],
            'descuento_tipo'         => ['nullable', 'in:fijo,porcentaje'],
        ]);
    }

    /**
     * @param  array<int,array<string,mixed>>  $detalles
     * @return Collection<int,array<string,mixed>>
     */
    private function construirDetalles(array $detalles): Collection
    {
        $costos = Producto::whereIn('id', collect($detalles)->pluck('producto_id'))
            ->pluck('precio_compra', 'id');

        return collect($detalles)->map(function ($item) use ($costos) {
            $cantidad = (int) $item['cantidad'];
            $precio = round((float) $item['precio'], 2);

            return [
                'producto_id'    => $item['producto_id'],
                'cantidad'       => $cantidad,
                'precio'         => $precio,
                'costo_unitario' => round((float) ($costos[$item['producto_id']] ?? 0), 2),
                'subtotal'       => round($cantidad * $precio, 2),
            ];
        });
    }

    /**
     * @param  array<int,array<string,mixed>>  $pagos
     */
    private function sincronizarPagos(Venta $venta, array $pagos, float $total): void
    {
        $venta->pagos()->delete();
        $pagado = 0.0;

        foreach ($pagos as $pago) {
            $monto = round((float) ($pago['monto'] ?? 0), 2);
            if (empty($pago['metodo_pago_id']) || $monto <= 0) {
                continue;
            }
            $venta->pagos()->create([
                'metodo_pago_id' => $pago['metodo_pago_id'],
                'monto'          => $monto,
                'referencia'     => $pago['referencia'] ?? null,
            ]);
            $pagado += $monto;
        }

        $venta->update([
            'pagado'      => $pagado,
            'estado_pago' => CalculadorTotales::estadoPago($total, $pagado),
        ]);
    }

    /**
     * @param  array<int,array<string,mixed>>  $pagos
     */
    private function excedeCredito(Cliente $cliente, float $total, array $pagos): ?string
    {
        $limite = (float) $cliente->limite_credito;
        if ($limite <= 0) {
            return null;
        }

        $aPagar = collect($pagos)->sum(fn ($p) => (float) ($p['monto'] ?? 0));
        $nuevoSaldo = max(0, $total - $aPagar);
        $proyectado = $cliente->saldo() + $nuevoSaldo;

        if ($proyectado > $limite + 0.01) {
            return 'Supera el límite de crédito del cliente ($'.number_format($limite, 2)
                .'). Deuda proyectada: $'.number_format($proyectado, 2).'.';
        }

        return null;
    }

    /**
     * @param  array<int,array<string,mixed>>  $pagos
     */
    private function registrarEfectivoEnCaja(Venta $venta, array $pagos): void
    {
        $efectivoIds = MetodoPago::where('codigo', 'efectivo')->pluck('id')->all();

        $monto = collect($pagos)
            ->filter(fn ($p) => in_array((int) ($p['metodo_pago_id'] ?? 0), $efectivoIds, true))
            ->sum(fn ($p) => (float) ($p['monto'] ?? 0));

        if ($monto > 0) {
            $this->caja->registrarMovimiento('ingreso', "Venta {$venta->numero}", $monto, 'venta', $venta->id);
        }
    }

    /**
     * Ajusta la caja por la diferencia de efectivo tras editar una venta.
     *
     * @param  array<int,array<string,mixed>>  $pagosNuevos
     */
    private function ajustarEfectivoEnCaja(Venta $venta, float $efectivoAnterior, array $pagosNuevos): void
    {
        $efectivoIds = MetodoPago::where('codigo', 'efectivo')->pluck('id')->all();
        $efectivoNuevo = collect($pagosNuevos)
            ->filter(fn ($p) => in_array((int) ($p['metodo_pago_id'] ?? 0), $efectivoIds, true))
            ->sum(fn ($p) => (float) ($p['monto'] ?? 0));

        $delta = round($efectivoNuevo - $efectivoAnterior, 2);
        if (abs($delta) < 0.01) {
            return;
        }

        $this->caja->registrarMovimiento(
            $delta > 0 ? 'ingreso' : 'egreso',
            "Ajuste edición venta {$venta->numero}",
            abs($delta),
            'venta',
            $venta->id
        );
    }

    private function generarNumero(): string
    {
        $prefijo = Setting::obtener('ventas_prefijo_numero', 'VTA');
        $digitos = (int) Setting::obtener('ventas_cantidad_digitos', '5');

        $ultima = Venta::where('numero', 'like', "{$prefijo}-%")
            ->orderByRaw('CAST(SUBSTRING(numero, '.(strlen($prefijo) + 2).') AS UNSIGNED) DESC')
            ->lockForUpdate()
            ->first();

        $nuevoNumero = $ultima ? ((int) substr($ultima->numero, strlen($prefijo) + 1)) + 1 : 1;

        return $prefijo.'-'.str_pad((string) $nuevoNumero, $digitos, '0', STR_PAD_LEFT);
    }
}
