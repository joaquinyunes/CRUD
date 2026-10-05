<?php

namespace App\Http\Controllers;

use App\Http\Requests\PresupuestoRequest;
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

    /**
     * El formulario busca clientes y productos contra sus endpoints en vez de
     * traer los catálogos embebidos en el HTML.
     */
    public function create(): View
    {
        return view('presupuestos.form', [
            'clienteElegido'  => null,
            'nombresProducto' => $this->nombresDeProductosEnPantalla(),
        ]);
    }

    public function store(PresupuestoRequest $request): RedirectResponse
    {
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
        abort_if((bool) $presupuesto->venta_id, 404, 'El presupuesto ya fue convertido en venta.');
        $presupuesto->load('detalles.producto', 'cliente');

        return view('presupuestos.form', [
            'presupuesto'     => $presupuesto,
            'clienteElegido'  => $presupuesto->cliente,
            'nombresProducto' => $this->nombresDeProductosEnPantalla($presupuesto),
        ]);
    }

    /**
     * Los nombres de los productos que el formulario va a mostrar, por id.
     *
     * Hacen falta para que el autocompletado arranque con el texto puesto. Sale
     * de las líneas guardadas o, si el formulario vuelve con errores, de lo que
     * el usuario había cargado.
     *
     * @return array<int,string>
     */
    private function nombresDeProductosEnPantalla(?Presupuesto $presupuesto = null): array
    {
        $ids = collect(old('detalles', $presupuesto?->detalles->toArray() ?? []))
            ->pluck('producto_id')
            ->filter()
            ->unique();

        if ($ids->isEmpty()) {
            return [];
        }

        return Producto::whereIn('id', $ids)->pluck('nombre', 'id')->all();
    }

    public function update(PresupuestoRequest $request, Presupuesto $presupuesto): RedirectResponse
    {
        abort_if((bool) $presupuesto->venta_id, 404);
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
        abort_if((bool) $presupuesto->venta_id, 404);
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
