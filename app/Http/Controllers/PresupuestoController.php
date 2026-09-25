<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Presupuesto;
use App\Models\Producto;
use App\Models\Setting;
use App\Models\Venta;
use App\Support\CalculadorTotales;
use App\Support\NumeradorDocumentos;
use App\Support\Orden;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PresupuestoController extends Controller
{
    public function index(Request $request): View
    {
        $query = Presupuesto::with('cliente', 'user')
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->estado))
            ->when($request->filled('buscar'), fn ($q) => $q->where('numero', 'like', "%{$request->buscar}%"));

        $presupuestos = Orden::aplicar($query, [
            'numero'  => 'numero',
            'cliente' => Cliente::select('nombre')->whereColumn('clientes.id', 'presupuestos.cliente_id'),
            'fecha'   => fn ($q, $dir) => $q->orderBy('fecha', $dir)->orderBy('id', $dir),
            'validez' => 'validez_dias',
            'estado'  => 'estado',
            'total'   => 'total',
        ], 'fecha', 'desc')
            ->paginate(20)->withQueryString();

        return view('presupuestos.index', compact('presupuestos'));
    }

    public function create(): View
    {
        $clientes = Cliente::where('estado', 'activo')->orderBy('nombre')->get();
        $productos = Producto::where('estado', 'activo')->orderBy('nombre')->get();

        return view('presupuestos.form', compact('clientes', 'productos'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->validar($request);

        DB::transaction(function () use ($request) {
            $detalles = $this->construirDetalles($request->detalles);
            $totales = CalculadorTotales::calcular($detalles->all(), $request->input('descuento_tipo'), (float) $request->input('descuento', 0));

            $presupuesto = Presupuesto::create([
                'numero'         => NumeradorDocumentos::proximo('presupuestos', 'PRE'),
                'cliente_id'     => $request->cliente_id,
                'fecha'          => $request->fecha,
                'validez_dias'   => (int) $request->input('validez_dias', 15),
                'subtotal'       => $totales['subtotal'],
                'descuento'      => $totales['descuento'],
                'descuento_tipo' => $request->input('descuento_tipo'),
                'impuesto'       => $totales['impuesto'],
                'total'          => $totales['total'],
                'estado'         => $request->input('estado', 'borrador'),
                'observaciones'  => $request->observaciones,
                'user_id'        => auth()->id(),
            ]);

            $presupuesto->detalles()->createMany($detalles->all());
        });

        return redirect()->route('presupuestos.index')->with('success', 'Presupuesto creado.');
    }

    public function show(Presupuesto $presupuesto): View
    {
        $presupuesto->load('detalles.producto', 'cliente', 'user', 'venta');

        return view('presupuestos.show', compact('presupuesto'));
    }

    public function edit(Presupuesto $presupuesto): View
    {
        abort_if($presupuesto->venta_id, 404, 'El presupuesto ya fue convertido en venta.');
        $presupuesto->load('detalles.producto');

        $clientes = Cliente::where('estado', 'activo')->orderBy('nombre')->get();
        $productos = Producto::where('estado', 'activo')->orderBy('nombre')->get();

        return view('presupuestos.form', compact('presupuesto', 'clientes', 'productos'));
    }

    public function update(Request $request, Presupuesto $presupuesto): RedirectResponse
    {
        abort_if($presupuesto->venta_id, 404);
        $this->validar($request);

        DB::transaction(function () use ($request, $presupuesto) {
            $detalles = $this->construirDetalles($request->detalles);
            $totales = CalculadorTotales::calcular($detalles->all(), $request->input('descuento_tipo'), (float) $request->input('descuento', 0));

            $presupuesto->update([
                'cliente_id'     => $request->cliente_id,
                'fecha'          => $request->fecha,
                'validez_dias'   => (int) $request->input('validez_dias', 15),
                'subtotal'       => $totales['subtotal'],
                'descuento'      => $totales['descuento'],
                'descuento_tipo' => $request->input('descuento_tipo'),
                'impuesto'       => $totales['impuesto'],
                'total'          => $totales['total'],
                'estado'         => $request->input('estado', $presupuesto->estado),
                'observaciones'  => $request->observaciones,
            ]);

            $presupuesto->detalles()->delete();
            $presupuesto->detalles()->createMany($detalles->all());
        });

        return redirect()->route('presupuestos.index')->with('success', 'Presupuesto actualizado.');
    }

    public function destroy(Presupuesto $presupuesto): RedirectResponse
    {
        abort_if($presupuesto->venta_id, 404);
        $presupuesto->delete();

        return redirect()->route('presupuestos.index')->with('success', 'Presupuesto eliminado.');
    }

    /**
     * Crea una venta en estado pendiente a partir del presupuesto y redirige
     * a su edición para que el usuario la complete (pagos, confirmación de stock).
     */
    public function convertir(Presupuesto $presupuesto): RedirectResponse
    {
        if (! $presupuesto->puedeConvertirse()) {
            return back()->withErrors(['general' => 'Este presupuesto no puede convertirse.']);
        }

        $presupuesto->load('detalles');

        $venta = DB::transaction(function () use ($presupuesto) {
            $costos = Producto::whereIn('id', $presupuesto->detalles->pluck('producto_id'))->pluck('precio_compra', 'id');

            $venta = Venta::create([
                'numero'         => NumeradorDocumentos::proximo('ventas', Setting::obtener('ventas_prefijo_numero', 'VTA'), (int) Setting::obtener('ventas_cantidad_digitos', '5')),
                'cliente_id'     => $presupuesto->cliente_id,
                'fecha'          => now()->toDateString(),
                'subtotal'       => $presupuesto->subtotal,
                'descuento'      => $presupuesto->descuento,
                'descuento_tipo' => $presupuesto->descuento_tipo,
                'impuesto'       => $presupuesto->impuesto,
                'total_final'    => $presupuesto->total,
                'total'          => $presupuesto->total,
                'estado'         => 'pendiente',
                'user_id'        => auth()->id(),
            ]);

            foreach ($presupuesto->detalles as $d) {
                $venta->detalles()->create([
                    'producto_id'    => $d->producto_id,
                    'cantidad'       => $d->cantidad,
                    'precio'         => $d->precio,
                    'costo_unitario' => round((float) ($costos[$d->producto_id] ?? 0), 2),
                    'subtotal'       => $d->subtotal,
                ]);
            }

            $presupuesto->update(['estado' => 'convertido', 'venta_id' => $venta->id]);

            return $venta;
        });

        return redirect()->route('ventas.edit', $venta)
            ->with('success', 'Venta creada desde el presupuesto. Revisá y confirmá para descontar stock.');
    }

    private function validar(Request $request): void
    {
        $request->validate([
            'cliente_id'             => ['required', 'exists:clientes,id'],
            'fecha'                  => ['required', 'date'],
            'validez_dias'           => ['nullable', 'integer', 'min:1', 'max:365'],
            'estado'                 => ['nullable', 'in:borrador,enviado,aceptado,rechazado'],
            'detalles'               => ['required', 'array', 'min:1'],
            'detalles.*.producto_id' => ['required', 'exists:productos,id'],
            'detalles.*.cantidad'    => ['required', 'integer', 'min:1'],
            'detalles.*.precio'      => ['required', 'numeric', 'min:0'],
            'descuento'              => ['nullable', 'numeric', 'min:0'],
            'descuento_tipo'         => ['nullable', 'in:fijo,porcentaje'],
            'observaciones'          => ['nullable', 'string'],
        ]);
    }

    private function construirDetalles(array $detalles): Collection
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
}
