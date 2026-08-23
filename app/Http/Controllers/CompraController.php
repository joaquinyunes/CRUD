<?php

namespace App\Http\Controllers;

use App\Events\CompraCreada;
use App\Models\Compra;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Setting;
use App\Services\StockDocumentoService;
use App\Support\CalculadorTotales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CompraController extends Controller
{
    public function __construct(private StockDocumentoService $stockDoc) {}

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
        $metodosPago = MetodoPago::activos()->get();
        $depositos = \App\Models\Deposito::activos()->orderByDesc('es_principal')->orderBy('nombre')->get();

        return view('compras.form', compact('proveedores', 'productos', 'metodosPago', 'depositos'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->validar($request);

        $compra = DB::transaction(function () use ($request) {
            $detalles = $this->construirDetalles($request->detalles);

            $totales = CalculadorTotales::calcular(
                $detalles->all(),
                $request->input('descuento_tipo'),
                (float) $request->input('descuento', 0)
            );

            $compra = Compra::create([
                'numero'         => $this->generarNumero(),
                'proveedor_id'   => $request->proveedor_id,
                'deposito_id'    => $request->deposito_id ?: \App\Models\Deposito::principalId(),
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

            $compra->detalles()->createMany($detalles->all());
            $this->sincronizarPagos($compra, $request->input('metodos_pago', []), $totales['total']);

            if ($compra->estado === 'completada') {
                $this->stockDoc->aplicarCompra($compra);
                $this->actualizarCostos($detalles->all());
            }

            return $compra;
        });

        CompraCreada::dispatch($compra);

        return redirect()->route('compras.index')->with('success', 'Compra registrada correctamente.');
    }

    public function edit(Compra $compra): View
    {
        $this->authorize('update', $compra);
        abort_if($compra->estado === 'anulada', 404);
        $compra->load(['detalles.producto', 'pagos.metodoPago']);

        $proveedores = Proveedor::orderBy('nombre')->get();
        $productos = Producto::where('estado', 'activo')->orderBy('nombre')->get();
        $metodosPago = MetodoPago::activos()->get();
        $depositos = \App\Models\Deposito::activos()->orderByDesc('es_principal')->orderBy('nombre')->get();

        return view('compras.form', compact('compra', 'proveedores', 'productos', 'metodosPago', 'depositos'));
    }

    public function update(Request $request, Compra $compra): RedirectResponse
    {
        $this->authorize('update', $compra);
        abort_if($compra->estado === 'anulada', 404);
        $this->validar($request);

        try {
            DB::transaction(function () use ($request, $compra) {
                $this->stockDoc->revertirCompra($compra);

                $detalles = $this->construirDetalles($request->detalles);
                $totales = CalculadorTotales::calcular(
                    $detalles->all(),
                    $request->input('descuento_tipo'),
                    (float) $request->input('descuento', 0)
                );

                $compra->update([
                    'proveedor_id'   => $request->proveedor_id,
                    'deposito_id'    => $request->deposito_id ?: $compra->deposito_id,
                    'fecha'          => $request->fecha,
                    'subtotal'       => $totales['subtotal'],
                    'descuento'      => $totales['descuento'],
                    'descuento_tipo' => $request->input('descuento_tipo'),
                    'impuesto'       => $totales['impuesto'],
                    'total_final'    => $totales['total'],
                    'total'          => $totales['total'],
                    'estado'         => $request->estado,
                ]);

                $compra->detalles()->delete();
                $compra->detalles()->createMany($detalles->all());
                $this->sincronizarPagos($compra, $request->input('metodos_pago', []), $totales['total']);

                if ($compra->estado === 'completada') {
                    $this->stockDoc->aplicarCompra($compra);
                    $this->actualizarCostos($detalles->all());
                }
            });
        } catch (\App\Exceptions\StockInsuficienteException $e) {
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
        } catch (\App\Exceptions\StockInsuficienteException $e) {
            return back()->withErrors(['general' => 'No se puede anular: ' . $e->getMessage()]);
        }

        return redirect()->route('compras.index')->with('success', 'Compra anulada correctamente.');
    }

    public function show(Compra $compra): View
    {
        $compra->load(['detalles.producto', 'proveedor', 'user']);

        return view('compras.show', compact('compra'));
    }

    private function validar(Request $request): void
    {
        $request->validate([
            'proveedor_id'           => ['required', 'exists:proveedores,id'],
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

    private function construirDetalles(array $detalles): \Illuminate\Support\Collection
    {
        return collect($detalles)->map(function ($item) {
            $cantidad = (int) $item['cantidad'];
            $precio = round((float) $item['precio'], 2);

            return [
                'producto_id' => $item['producto_id'],
                'cantidad'    => $cantidad,
                'precio'      => $precio,
                'subtotal'    => round($cantidad * $precio, 2),
            ];
        });
    }

    /**
     * Sincroniza el precio_compra del producto con el último costo real pagado.
     */
    private function actualizarCostos(array $detalles): void
    {
        foreach ($detalles as $d) {
            Producto::whereKey($d['producto_id'])->update(['precio_compra' => $d['precio']]);
        }
    }

    private function sincronizarPagos(Compra $compra, array $pagos, float $total): void
    {
        $compra->pagos()->delete();
        $pagado = 0.0;

        foreach ($pagos as $pago) {
            $monto = round((float) ($pago['monto'] ?? 0), 2);
            if (empty($pago['metodo_pago_id']) || $monto <= 0) {
                continue;
            }
            $compra->pagos()->create([
                'metodo_pago_id' => $pago['metodo_pago_id'],
                'monto'          => $monto,
                'referencia'     => $pago['referencia'] ?? null,
            ]);
            $pagado += $monto;
        }

        $compra->update([
            'pagado'      => $pagado,
            'estado_pago' => CalculadorTotales::estadoPago($total, $pagado),
        ]);
    }

    private function generarNumero(): string
    {
        $prefijo = Setting::obtener('compras_prefijo_numero', 'COM');
        $digitos = (int) Setting::obtener('compras_cantidad_digitos', '5');

        $ultima = Compra::where('numero', 'like', "{$prefijo}-%")
                        ->orderByRaw('CAST(SUBSTRING(numero, ' . (strlen($prefijo) + 2) . ') AS UNSIGNED) DESC')
                        ->lockForUpdate()
                        ->first();

        $nuevoNumero = $ultima ? ((int) substr($ultima->numero, strlen($prefijo) + 1)) + 1 : 1;

        return $prefijo . '-' . str_pad((string) $nuevoNumero, $digitos, '0', STR_PAD_LEFT);
    }
}
