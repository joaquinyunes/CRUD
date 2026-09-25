<?php

namespace App\Http\Controllers;

use App\Exceptions\StockInsuficienteException;
use App\Models\Compra;
use App\Models\Devolucion;
use App\Models\Producto;
use App\Models\Venta;
use App\Services\CajaService;
use App\Services\StockService;
use App\Support\Orden;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DevolucionController extends Controller
{
    public function __construct(
        private StockService $stock,
        private CajaService $caja,
    ) {}

    public function index(Request $request): View
    {
        $query = Devolucion::with(['venta.cliente', 'compra.proveedor', 'user'])
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

    public function show(Devolucion $devolucion): View
    {
        $devolucion->load(['detalles.producto', 'venta.cliente', 'compra.proveedor', 'user']);

        return view('devoluciones.show', compact('devolucion'));
    }

    public function createVenta(Venta $venta): View
    {
        abort_if($venta->estado === 'anulada', 404);
        $venta->load('detalles.producto');
        $devueltos = $this->devueltosPorProducto('venta', $venta->id);

        return view('devoluciones.form-venta', compact('venta', 'devueltos'));
    }

    public function storeVenta(Request $request, Venta $venta): RedirectResponse
    {
        abort_if($venta->estado === 'anulada', 404);

        $request->validate([
            'motivo'                 => ['nullable', 'string', 'max:255'],
            'reembolso_efectivo'     => ['nullable', 'boolean'],
            'detalles'               => ['required', 'array', 'min:1'],
            'detalles.*.producto_id' => ['required', 'exists:productos,id'],
            'detalles.*.cantidad'    => ['required', 'integer', 'min:0'],
        ]);

        $venta->load('detalles');
        $devueltos = $this->devueltosPorProducto('venta', $venta->id);
        $lineas = [];

        foreach ($request->detalles as $d) {
            if ((int) $d['cantidad'] <= 0) {
                continue;
            }
            $original = $venta->detalles->firstWhere('producto_id', (int) $d['producto_id']);
            if (! $original) {
                return back()->withErrors(['detalles' => 'Un producto no pertenece a la venta.'])->withInput();
            }
            $disponible = $original->cantidad - ($devueltos[$original->producto_id] ?? 0);
            if ($d['cantidad'] > $disponible) {
                return back()->withErrors(['detalles' => "No podés devolver más de {$disponible} de «{$original->producto->nombre}»."])->withInput();
            }
            $lineas[] = [
                'producto_id' => $original->producto_id,
                'cantidad'    => (int) $d['cantidad'],
                'precio'      => (float) $original->precio,
                'subtotal'    => round($d['cantidad'] * $original->precio, 2),
            ];
        }

        if (empty($lineas)) {
            return back()->withErrors(['detalles' => 'Ingresá al menos una cantidad a devolver.'])->withInput();
        }

        try {
            DB::transaction(function () use ($venta, $request, $lineas) {
                $devolucion = Devolucion::create([
                    'numero'   => $this->generarNumero(),
                    'tipo'     => 'venta',
                    'venta_id' => $venta->id,
                    'fecha'    => now()->toDateString(),
                    'motivo'   => $request->motivo,
                    'total'    => round(collect($lineas)->sum('subtotal'), 2),
                    'estado'   => 'registrada',
                    'user_id'  => auth()->id(),
                ]);

                foreach ($lineas as $l) {
                    $devolucion->detalles()->create($l);
                    $this->stock->registrarDevolucion(
                        Producto::find($l['producto_id']),
                        $l['cantidad'],
                        'devolucion_venta',
                        $devolucion->id
                    );
                }

                if ($request->boolean('reembolso_efectivo')) {
                    $this->caja->registrarMovimiento('egreso', "Reembolso devolución {$devolucion->numero}", (float) $devolucion->total, 'devolucion', $devolucion->id);
                }
            });
        } catch (StockInsuficienteException $e) {
            return back()->withErrors(['detalles' => $e->getMessage()])->withInput();
        }

        return redirect()->route('ventas.show', $venta)->with('success', 'Devolución registrada. El stock fue reingresado.');
    }

    public function createCompra(Compra $compra): View
    {
        abort_if($compra->estado === 'anulada', 404);
        $compra->load('detalles.producto');
        $devueltos = $this->devueltosPorProducto('compra', $compra->id);

        return view('devoluciones.form-compra', compact('compra', 'devueltos'));
    }

    public function storeCompra(Request $request, Compra $compra): RedirectResponse
    {
        abort_if($compra->estado === 'anulada', 404);

        $request->validate([
            'motivo'                 => ['nullable', 'string', 'max:255'],
            'detalles'               => ['required', 'array', 'min:1'],
            'detalles.*.producto_id' => ['required', 'exists:productos,id'],
            'detalles.*.cantidad'    => ['required', 'integer', 'min:0'],
        ]);

        $compra->load('detalles');
        $devueltos = $this->devueltosPorProducto('compra', $compra->id);
        $lineas = [];

        foreach ($request->detalles as $d) {
            if ((int) $d['cantidad'] <= 0) {
                continue;
            }
            $original = $compra->detalles->firstWhere('producto_id', (int) $d['producto_id']);
            if (! $original) {
                return back()->withErrors(['detalles' => 'Un producto no pertenece a la compra.'])->withInput();
            }
            $disponible = $original->cantidad - ($devueltos[$original->producto_id] ?? 0);
            if ($d['cantidad'] > $disponible) {
                return back()->withErrors(['detalles' => "No podés devolver más de {$disponible}."])->withInput();
            }
            $lineas[] = [
                'producto_id' => $original->producto_id,
                'cantidad'    => (int) $d['cantidad'],
                'precio'      => (float) $original->precio,
                'subtotal'    => round($d['cantidad'] * $original->precio, 2),
            ];
        }

        if (empty($lineas)) {
            return back()->withErrors(['detalles' => 'Ingresá al menos una cantidad a devolver.'])->withInput();
        }

        try {
            DB::transaction(function () use ($compra, $request, $lineas) {
                $devolucion = Devolucion::create([
                    'numero'    => $this->generarNumero(),
                    'tipo'      => 'compra',
                    'compra_id' => $compra->id,
                    'fecha'     => now()->toDateString(),
                    'motivo'    => $request->motivo,
                    'total'     => round(collect($lineas)->sum('subtotal'), 2),
                    'estado'    => 'registrada',
                    'user_id'   => auth()->id(),
                ]);

                foreach ($lineas as $l) {
                    $devolucion->detalles()->create($l);
                    $this->stock->registrarSalida(
                        Producto::find($l['producto_id']),
                        $l['cantidad'],
                        'devolucion_compra',
                        $devolucion->id
                    );
                }
            });
        } catch (StockInsuficienteException $e) {
            return back()->withErrors(['detalles' => $e->getMessage()])->withInput();
        }

        return redirect()->route('compras.show', $compra)->with('success', 'Devolución a proveedor registrada. El stock fue descontado.');
    }

    /** @return array<int,int> producto_id => cantidad ya devuelta */
    private function devueltosPorProducto(string $tipo, int $docId): array
    {
        $col = $tipo === 'venta' ? 'venta_id' : 'compra_id';

        return DB::table('devoluciones_detalle')
            ->join('devoluciones', 'devoluciones.id', '=', 'devoluciones_detalle.devolucion_id')
            ->where('devoluciones.tipo', $tipo)
            ->where("devoluciones.{$col}", $docId)
            ->where('devoluciones.estado', 'registrada')
            ->groupBy('devoluciones_detalle.producto_id')
            ->pluck(DB::raw('SUM(devoluciones_detalle.cantidad)'), 'devoluciones_detalle.producto_id')
            ->map(fn ($v) => (int) $v)->toArray();
    }

    private function generarNumero(): string
    {
        $ultimo = Devolucion::where('numero', 'like', 'DEV-%')
            ->orderByRaw('CAST(SUBSTRING(numero, 5) AS UNSIGNED) DESC')
            ->lockForUpdate()->first();

        $n = $ultimo ? ((int) substr($ultimo->numero, 4)) + 1 : 1;

        return 'DEV-'.str_pad((string) $n, 5, '0', STR_PAD_LEFT);
    }
}
