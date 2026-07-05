<?php

namespace App\Http\Controllers;

use App\Events\CompraCreada;
use App\Models\Compra;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CompraController extends Controller
{
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

        return view('compras.form', compact('proveedores', 'productos', 'metodosPago'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'proveedor_id'  => ['required', 'exists:proveedores,id'],
            'fecha'         => ['required', 'date'],
            'estado'        => ['required', 'in:pendiente,completada,cancelada'],
            'detalles'      => ['required', 'array', 'min:1'],
            'detalles.*.producto_id' => ['required', 'exists:productos,id'],
            'detalles.*.cantidad'    => ['required', 'integer', 'min:1'],
            'detalles.*.precio'      => ['required', 'numeric', 'min:0'],
        ]);

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

            $compra = Compra::create([
                'numero'      => $numero,
                'proveedor_id' => $request->proveedor_id,
                'fecha'       => $request->fecha,
                'total'       => $total,
                'estado'      => $request->estado,
                'user_id'     => auth()->id(),
            ]);

            foreach ($detalles as $detalle) {
                $compra->detalles()->create($detalle);
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

            $compra->update([
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
                        $compra->pagos()->create([
                            'metodo_pago_id' => $pago['metodo_pago_id'],
                            'monto' => $pago['monto'],
                            'referencia' => $pago['referencia'] ?? null,
                        ]);
                    }
                }
            }
        });

        CompraCreada::dispatch($compra);

        return redirect()->route('compras.index')
                         ->with('success', 'Compra registrada correctamente.');
    }

    public function edit(Compra $compra): View
    {
        $compra->load(['detalles.producto', 'pagos.metodoPago']);

        $proveedores = Proveedor::orderBy('nombre')->get();
        $productos = Producto::where('estado', 'activo')->orderBy('nombre')->get();
        $metodosPago = MetodoPago::activos()->get();

        return view('compras.form', compact('compra', 'proveedores', 'productos', 'metodosPago'));
    }

    public function update(Request $request, Compra $compra): RedirectResponse
    {
        $request->validate([
            'proveedor_id'  => ['required', 'exists:proveedores,id'],
            'fecha'         => ['required', 'date'],
            'estado'        => ['required', 'in:pendiente,completada,cancelada'],
            'detalles'      => ['required', 'array', 'min:1'],
            'detalles.*.producto_id' => ['required', 'exists:productos,id'],
            'detalles.*.cantidad'    => ['required', 'integer', 'min:1'],
            'detalles.*.precio'      => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($request, $compra) {
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

            $compra->update([
                'proveedor_id' => $request->proveedor_id,
                'fecha'        => $request->fecha,
                'total'        => $totalFinal,
                'subtotal'     => $subtotal,
                'descuento'    => $descuento,
                'descuento_tipo' => $descuentoTipo,
                'impuesto'     => $impuesto,
                'total_final'  => $totalFinal,
                'estado'       => $request->estado,
            ]);

            $compra->detalles()->delete();

            foreach ($detalles as $detalle) {
                $compra->detalles()->create($detalle);
            }

            $compra->pagos()->delete();

            if ($request->filled('metodos_pago')) {
                foreach ($request->metodos_pago as $pago) {
                    if (!empty($pago['metodo_pago_id']) && !empty($pago['monto']) && $pago['monto'] > 0) {
                        $compra->pagos()->create([
                            'metodo_pago_id' => $pago['metodo_pago_id'],
                            'monto' => $pago['monto'],
                            'referencia' => $pago['referencia'] ?? null,
                        ]);
                    }
                }
            }
        });

        return redirect()->route('compras.index')
                         ->with('success', 'Compra actualizada correctamente.');
    }

    public function destroy(Compra $compra): RedirectResponse
    {
        $compra->delete();

        return redirect()->route('compras.index')
                         ->with('success', 'Compra eliminada correctamente.');
    }

    public function show(Compra $compra): View
    {
        $compra->load(['detalles.producto', 'proveedor', 'user']);

        return view('compras.show', compact('compra'));
    }

    private function generarNumero(): string
    {
        $prefijo = Setting::obtener('compras_prefijo_numero', 'COM');
        $digitos = (int) Setting::obtener('compras_cantidad_digitos', '5');

        $ultima = Compra::where('numero', 'like', "{$prefijo}-%")
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
