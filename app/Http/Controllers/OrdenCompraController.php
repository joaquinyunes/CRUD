<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\Deposito;
use App\Models\OrdenCompra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Setting;
use App\Services\StockService;
use App\Support\CalculadorTotales;
use App\Support\NumeradorDocumentos;
use App\Support\Orden;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrdenCompraController extends Controller
{
    public function __construct(private StockService $stock) {}

    public function index(Request $request): View
    {
        $query = OrdenCompra::with('proveedor', 'user')
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->estado));

        $ordenes = Orden::aplicar($query, [
            'numero'    => 'numero',
            'proveedor' => Proveedor::select('nombre')->whereColumn('proveedores.id', 'ordenes_compra.proveedor_id'),
            'fecha'     => fn ($q, $dir) => $q->orderBy('fecha', $dir)->orderBy('id', $dir),
            'entrega'   => 'fecha_entrega_estimada',
            'estado'    => 'estado',
            'total'     => 'total',
        ], 'fecha', 'desc')
            ->paginate(20)->withQueryString();

        return view('ordenes_compra.index', compact('ordenes'));
    }

    public function create(): View
    {
        $proveedores = Proveedor::orderBy('nombre')->get();
        $productos = Producto::where('estado', 'activo')->orderBy('nombre')->get();
        $depositos = Deposito::activos()->orderByDesc('es_principal')->orderBy('nombre')->get();

        return view('ordenes_compra.form', compact('proveedores', 'productos', 'depositos'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->validar($request);

        DB::transaction(function () use ($request) {
            $detalles = $this->construirDetalles($request->detalles);

            $orden = OrdenCompra::create([
                'numero'                 => NumeradorDocumentos::proximo('ordenes_compra', 'OC'),
                'proveedor_id'           => $request->proveedor_id,
                'deposito_id'            => $request->deposito_id ?: Deposito::principalId(),
                'fecha'                  => $request->fecha,
                'fecha_entrega_estimada' => $request->fecha_entrega_estimada,
                'total'                  => round($detalles->sum('subtotal'), 2),
                'estado'                 => $request->input('estado', 'borrador'),
                'observaciones'          => $request->observaciones,
                'user_id'                => auth()->id(),
            ]);

            $orden->detalles()->createMany($detalles->all());
        });

        return redirect()->route('ordenes-compra.index')->with('success', 'Orden de compra creada.');
    }

    public function show(OrdenCompra $ordenCompra): View
    {
        $orden = $ordenCompra->load('detalles.producto', 'proveedor', 'user', 'compra');

        return view('ordenes_compra.show', compact('orden'));
    }

    public function edit(OrdenCompra $ordenCompra): View
    {
        $orden = $ordenCompra;
        abort_if($orden->tieneRecepciones() || $orden->compra_id, 404, 'La orden ya tiene recepciones o factura.');
        $orden->load('detalles.producto');

        $proveedores = Proveedor::orderBy('nombre')->get();
        $productos = Producto::where('estado', 'activo')->orderBy('nombre')->get();
        $depositos = Deposito::activos()->orderByDesc('es_principal')->orderBy('nombre')->get();

        return view('ordenes_compra.form', compact('orden', 'proveedores', 'productos', 'depositos'));
    }

    public function update(Request $request, OrdenCompra $ordenCompra): RedirectResponse
    {
        $orden = $ordenCompra;
        abort_if($orden->tieneRecepciones() || $orden->compra_id, 404);
        $this->validar($request);

        DB::transaction(function () use ($request, $orden) {
            $detalles = $this->construirDetalles($request->detalles);

            $orden->update([
                'proveedor_id'           => $request->proveedor_id,
                'deposito_id'            => $request->deposito_id ?: $orden->deposito_id,
                'fecha'                  => $request->fecha,
                'fecha_entrega_estimada' => $request->fecha_entrega_estimada,
                'total'                  => round($detalles->sum('subtotal'), 2),
                'estado'                 => $request->input('estado', $orden->estado),
                'observaciones'          => $request->observaciones,
            ]);

            $orden->detalles()->delete();
            $orden->detalles()->createMany($detalles->all());
        });

        return redirect()->route('ordenes-compra.index')->with('success', 'Orden actualizada.');
    }

    public function destroy(OrdenCompra $ordenCompra): RedirectResponse
    {
        abort_if($ordenCompra->tieneRecepciones() || $ordenCompra->compra_id, 404);
        $ordenCompra->update(['estado' => 'cancelada']);

        return redirect()->route('ordenes-compra.index')->with('success', 'Orden cancelada.');
    }

    public function recepcionForm(OrdenCompra $ordenCompra): View
    {
        $orden = $ordenCompra->load('detalles.producto');
        abort_if(in_array($orden->estado, ['recibida', 'cancelada'], true), 404);

        return view('ordenes_compra.recibir', compact('orden'));
    }

    /**
     * Registra una recepción (parcial o total): suma cantidades recibidas
     * y hace la entrada de stock por lo recibido en este acto.
     */
    public function recibir(Request $request, OrdenCompra $ordenCompra): RedirectResponse
    {
        $orden = $ordenCompra;
        abort_if(in_array($orden->estado, ['recibida', 'cancelada'], true), 404);

        $request->validate([
            'recibido'   => ['required', 'array'],
            'recibido.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $orden->load('detalles');
        $algo = false;

        DB::transaction(function () use ($request, $orden, &$algo) {
            foreach ($orden->detalles as $det) {
                $cant = (int) ($request->input("recibido.{$det->id}", 0));
                if ($cant <= 0) {
                    continue;
                }
                $cant = min($cant, $det->pendiente());
                if ($cant <= 0) {
                    continue;
                }

                $det->increment('cantidad_recibida', $cant);
                $this->stock->registrarEntrada($det->producto, $cant, 'orden_compra', $orden->id, $orden->deposito_id);
                $algo = true;
            }

            if ($algo) {
                $orden->refresh()->load('detalles');
                $orden->update(['estado' => $orden->totalmenteRecibida() ? 'recibida' : 'parcial']);
            }
        });

        if (! $algo) {
            return back()->withErrors(['recibido' => 'Ingresá al menos una cantidad a recibir.']);
        }

        return redirect()->route('ordenes-compra.show', $orden)->with('success', 'Recepción registrada. Stock actualizado.');
    }

    /**
     * Genera la compra (deuda con el proveedor) a partir de lo recibido.
     * No vuelve a mover stock: la recepción ya lo hizo.
     */
    public function facturar(Request $request, OrdenCompra $ordenCompra): RedirectResponse
    {
        $orden = $ordenCompra->load('detalles.producto');

        if ($orden->compra_id) {
            return back()->withErrors(['general' => 'La orden ya está facturada.']);
        }
        if (! $orden->tieneRecepciones()) {
            return back()->withErrors(['general' => 'Registrá al menos una recepción antes de facturar.']);
        }

        $compra = DB::transaction(function () use ($orden) {
            $lineas = $orden->detalles
                ->filter(fn ($d) => $d->cantidad_recibida > 0)
                ->map(fn ($d) => [
                    'producto_id' => $d->producto_id,
                    'cantidad'    => $d->cantidad_recibida,
                    'precio'      => (float) $d->precio,
                    'subtotal'    => round($d->cantidad_recibida * $d->precio, 2),
                ])->values();

            $totales = CalculadorTotales::calcular($lineas->all(), null, 0);

            $compra = Compra::create([
                'numero'         => NumeradorDocumentos::proximo('compras', Setting::obtener('compras_prefijo_numero', 'COM'), (int) Setting::obtener('compras_cantidad_digitos', '5')),
                'proveedor_id'   => $orden->proveedor_id,
                'fecha'          => now()->toDateString(),
                'subtotal'       => $totales['subtotal'],
                'impuesto'       => $totales['impuesto'],
                'total_final'    => $totales['total'],
                'total'          => $totales['total'],
                'estado'         => 'completada',
                'estado_pago'    => 'impago',
                'stock_aplicado' => true, // la recepción de la OC ya movió el stock
                'user_id'        => auth()->id(),
            ]);

            $compra->detalles()->createMany($lineas->all());

            foreach ($lineas as $l) {
                Producto::whereKey($l['producto_id'])->update(['precio_compra' => $l['precio']]);
            }

            $orden->update(['compra_id' => $compra->id]);

            return $compra;
        });

        return redirect()->route('compras.show', $compra)
            ->with('success', 'Compra generada desde la orden. Cargá los pagos en la cuenta corriente del proveedor.');
    }

    private function validar(Request $request): void
    {
        $request->validate([
            'proveedor_id'           => ['required', 'exists:proveedores,id'],
            'deposito_id'            => ['nullable', 'exists:depositos,id'],
            'fecha'                  => ['required', 'date'],
            'fecha_entrega_estimada' => ['nullable', 'date'],
            'estado'                 => ['nullable', 'in:borrador,enviada'],
            'detalles'               => ['required', 'array', 'min:1'],
            'detalles.*.producto_id' => ['required', 'exists:productos,id'],
            'detalles.*.cantidad'    => ['required', 'integer', 'min:1'],
            'detalles.*.precio'      => ['required', 'numeric', 'min:0'],
            'observaciones'          => ['nullable', 'string'],
        ]);
    }

    private function construirDetalles(array $detalles): Collection
    {
        return collect($detalles)->map(function ($item) {
            $cantidad = (int) $item['cantidad'];
            $precio = round((float) $item['precio'], 2);

            return [
                'producto_id'       => $item['producto_id'],
                'cantidad'          => $cantidad,
                'cantidad_recibida' => 0,
                'precio'            => $precio,
                'subtotal'          => round($cantidad * $precio, 2),
            ];
        });
    }
}
