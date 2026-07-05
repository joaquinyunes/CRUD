<?php

namespace App\Http\Controllers;

use App\Events\VentaCreada;
use App\Models\Cliente;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\Setting;
use App\Models\Venta;
use App\Models\VentaPago;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class VentaController extends Controller
{
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

        $ventas = $query->orderBy('fecha', 'desc')
                        ->orderBy('id', 'desc')
                        ->paginate(20)
                        ->withQueryString();

        return view('ventas.index', compact('ventas'));
    }

    public function create(): View
    {
        $clientes = Cliente::where('estado', 'activo')->orderBy('nombre')->get();
        $productos = Producto::where('estado', 'activo')->orderBy('nombre')->get();
        $metodosPago = MetodoPago::activos()->get();

        return view('ventas.form', compact('clientes', 'productos', 'metodosPago'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'cliente_id'   => ['required', 'exists:clientes,id'],
            'fecha'        => ['required', 'date'],
            'estado'       => ['required', 'in:pendiente,completada,cancelada'],
            'detalles'     => ['required', 'array', 'min:1'],
            'detalles.*.producto_id' => ['required', 'exists:productos,id'],
            'detalles.*.cantidad'    => ['required', 'integer', 'min:1'],
            'detalles.*.precio'      => ['required', 'numeric', 'min:0'],
        ]);

        $cliente = Cliente::findOrFail($request->cliente_id);
        if ($cliente->estado !== 'activo') {
            return back()->withErrors(['cliente_id' => 'El cliente seleccionado no está activo.'])->withInput();
        }

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

            $venta = Venta::create([
                'numero'     => $numero,
                'cliente_id' => $request->cliente_id,
                'fecha'      => $request->fecha,
                'total'      => $total,
                'estado'     => $request->estado,
                'user_id'    => auth()->id(),
            ]);

            foreach ($detalles as $detalle) {
                $venta->detalles()->create($detalle);
            }

            $subtotal = $detalles->sum('subtotal');
            $descuentoTipo = $request->input('descuento_tipo');
            $descuentoValor = (float) $request->input('descuento', 0);
            $descuento = $descuentoTipo === 'porcentaje' ? ($subtotal * $descuentoValor / 100) : $descuentoValor;

            $ivaHabilitado = Setting::obtener('sistema_impuesto_habilitado', '1') === '1';
            $ivaPorcentaje = (float) Setting::obtener('sistema_iva', '21');
            $baseImponible = $subtotal - $descuento;
            $impuesto = $ivaHabilitado ? ($baseImponible * $ivaPorcentaje / 100) : 0;
            $totalFinal = $baseImponible + $impuesto;

            $venta->update([
                'subtotal' => $subtotal,
                'descuento' => $descuento,
                'descuento_tipo' => $descuentoTipo,
                'impuesto' => $impuesto,
                'total_final' => $totalFinal,
                'total' => $totalFinal,
            ]);

            if ($request->filled('metodos_pago')) {
                foreach ($request->metodos_pago as $pago) {
                    if (!empty($pago['metodo_pago_id']) && !empty($pago['monto']) && $pago['monto'] > 0) {
                        $venta->pagos()->create([
                            'metodo_pago_id' => $pago['metodo_pago_id'],
                            'monto' => $pago['monto'],
                            'referencia' => $pago['referencia'] ?? null,
                        ]);
                    }
                }
            }
        });

        VentaCreada::dispatch($venta);

        return redirect()->route('ventas.index')
                         ->with('success', 'Venta registrada correctamente.');
    }

    public function edit(Venta $venta): View
    {
        $venta->load(['detalles.producto', 'pagos.metodoPago']);

        $clientes = Cliente::where('estado', 'activo')->orderBy('nombre')->get();
        $productos = Producto::where('estado', 'activo')->orderBy('nombre')->get();
        $metodosPago = MetodoPago::activos()->get();

        return view('ventas.form', compact('venta', 'clientes', 'productos', 'metodosPago'));
    }

    public function update(Request $request, Venta $venta): RedirectResponse
    {
        $request->validate([
            'cliente_id'   => ['required', 'exists:clientes,id'],
            'fecha'        => ['required', 'date'],
            'estado'       => ['required', 'in:pendiente,completada,cancelada'],
            'detalles'     => ['required', 'array', 'min:1'],
            'detalles.*.producto_id' => ['required', 'exists:productos,id'],
            'detalles.*.cantidad'    => ['required', 'integer', 'min:1'],
            'detalles.*.precio'      => ['required', 'numeric', 'min:0'],
        ]);

        $cliente = Cliente::findOrFail($request->cliente_id);
        if ($cliente->estado !== 'activo') {
            return back()->withErrors(['cliente_id' => 'El cliente seleccionado no está activo.'])->withInput();
        }

        DB::transaction(function () use ($request, $venta) {
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

            $subtotal = $detalles->sum('subtotal');
            $descuentoTipo = $request->input('descuento_tipo');
            $descuentoValor = (float) $request->input('descuento', 0);
            $descuento = $descuentoTipo === 'porcentaje' ? ($subtotal * $descuentoValor / 100) : $descuentoValor;

            $ivaHabilitado = Setting::obtener('sistema_impuesto_habilitado', '1') === '1';
            $ivaPorcentaje = (float) Setting::obtener('sistema_iva', '21');
            $baseImponible = $subtotal - $descuento;
            $impuesto = $ivaHabilitado ? ($baseImponible * $ivaPorcentaje / 100) : 0;
            $totalFinal = $baseImponible + $impuesto;

            $venta->update([
                'cliente_id' => $request->cliente_id,
                'fecha'      => $request->fecha,
                'total'      => $totalFinal,
                'subtotal'   => $subtotal,
                'descuento'  => $descuento,
                'descuento_tipo' => $descuentoTipo,
                'impuesto'   => $impuesto,
                'total_final' => $totalFinal,
                'estado'     => $request->estado,
            ]);

            $venta->detalles()->delete();

            foreach ($detalles as $detalle) {
                $venta->detalles()->create($detalle);
            }

            $venta->pagos()->delete();

            if ($request->filled('metodos_pago')) {
                foreach ($request->metodos_pago as $pago) {
                    if (!empty($pago['metodo_pago_id']) && !empty($pago['monto']) && $pago['monto'] > 0) {
                        $venta->pagos()->create([
                            'metodo_pago_id' => $pago['metodo_pago_id'],
                            'monto' => $pago['monto'],
                            'referencia' => $pago['referencia'] ?? null,
                        ]);
                    }
                }
            }
        });

        return redirect()->route('ventas.index')
                         ->with('success', 'Venta actualizada correctamente.');
    }

    public function destroy(Venta $venta): RedirectResponse
    {
        $venta->delete();

        return redirect()->route('ventas.index')
                         ->with('success', 'Venta eliminada correctamente.');
    }

    public function show(Venta $venta): View
    {
        $venta->load(['detalles.producto', 'cliente', 'user', 'pagos.metodoPago']);

        return view('ventas.show', compact('venta'));
    }

    private function generarNumero(): string
    {
        $prefijo = Setting::obtener('ventas_prefijo_numero', 'VTA');
        $digitos = (int) Setting::obtener('ventas_cantidad_digitos', '5');

        $ultima = Venta::where('numero', 'like', "{$prefijo}-%")
                       ->orderByRaw("CAST(SUBSTRING(numero, " . (strlen($prefijo) + 2) . ") AS UNSIGNED) DESC")
                       ->first();

        if ($ultima) {
            $ultimoNumero = (int) substr($ultima->numero, strlen($prefijo) + 1);
            $nuevoNumero = $ultimoNumero + 1;
        } else {
            $nuevoNumero = 1;
        }

        return $prefijo . '-' . str_pad($nuevoNumero, $digitos, '0', STR_PAD_LEFT);
    }
}
