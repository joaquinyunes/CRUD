<?php

namespace App\Http\Controllers;

use App\Models\Deposito;
use App\Models\OrdenCompra;
use App\Models\Producto;
use App\Support\NumeradorDocumentos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Sugerencia de compra por rotación: para cada producto calcula el consumo
 * diario promedio y cuántos días de stock quedan, y propone una cantidad a
 * pedir para llegar a `$coberturaObjetivo` días. Agrupa por proveedor habitual.
 */
class ReposicionController extends Controller
{
    public function index(Request $request): View
    {
        $dias = (int) $request->get('dias', 30);
        $coberturaObjetivo = (int) $request->get('cobertura', 21);

        $desde = now()->subDays($dias)->toDateString();

        $vendidoPorProducto = DB::table('ventas_detalle')
            ->join('ventas', 'ventas_detalle.venta_id', '=', 'ventas.id')
            ->where('ventas.estado', 'completada')
            ->where('ventas.fecha', '>=', $desde)
            ->groupBy('ventas_detalle.producto_id')
            ->selectRaw('ventas_detalle.producto_id, SUM(ventas_detalle.cantidad) as total')
            ->pluck('total', 'producto_id');

        $productos = Producto::where('estado', 'activo')->with('proveedor')->get();

        $sugerencias = $productos->map(function (Producto $p) use ($vendidoPorProducto, $dias, $coberturaObjetivo) {
            $vendido = (float) ($vendidoPorProducto[$p->id] ?? 0);
            $porDia = $dias > 0 ? $vendido / $dias : 0;
            $cobertura = $porDia > 0 ? round($p->stock / $porDia, 1) : null;
            $objetivoUnidades = (int) ceil($porDia * $coberturaObjetivo);
            $sugerido = max(0, $objetivoUnidades - (int) $p->stock);

            return [
                'producto' => $p,
                'vendido' => $vendido,
                'por_dia' => round($porDia, 2),
                'stock' => (int) $p->stock,
                'punto_pedido' => (int) $p->punto_pedido,
                'cobertura' => $cobertura,
                'sugerido' => $sugerido,
            ];
        })->filter(function ($s) {
            return $s['sugerido'] > 0
                && ($s['por_dia'] > 0 || $s['stock'] <= $s['punto_pedido']);
        })->sortBy('cobertura')->values();

        $porProveedor = $sugerencias->groupBy(fn ($s) => $s['producto']->proveedor?->nombre ?? 'Sin proveedor');

        return view('reposicion.index', compact('porProveedor', 'dias', 'coberturaObjetivo'));
    }

    public function generar(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'proveedor_id' => ['required', 'exists:proveedores,id'],
            'lineas' => ['required', 'array', 'min:1'],
            'lineas.*.producto_id' => ['required', 'exists:productos,id'],
            'lineas.*.cantidad' => ['required', 'integer', 'min:1'],
        ]);

        $productos = Producto::whereIn('id', collect($data['lineas'])->pluck('producto_id'))->get()->keyBy('id');

        DB::transaction(function () use ($data, $productos) {
            $detalles = collect($data['lineas'])->map(function ($l) use ($productos) {
                $prod = $productos[$l['producto_id']];
                $precio = round((float) $prod->precio_compra, 2);

                return [
                    'producto_id' => $prod->id,
                    'cantidad' => (int) $l['cantidad'],
                    'precio' => $precio,
                    'subtotal' => round($precio * (int) $l['cantidad'], 2),
                ];
            });

            $orden = OrdenCompra::create([
                'numero' => NumeradorDocumentos::proximo('ordenes_compra', 'OC'),
                'proveedor_id' => $data['proveedor_id'],
                'deposito_id' => Deposito::principalId(),
                'fecha' => now()->toDateString(),
                'total' => round($detalles->sum('subtotal'), 2),
                'estado' => 'borrador',
                'observaciones' => 'Generada desde sugerencia de reposición.',
                'user_id' => auth()->id(),
            ]);

            $orden->detalles()->createMany($detalles->all());
        });

        return redirect()->route('ordenes-compra.index')->with('success', 'Orden de compra generada desde la sugerencia.');
    }
}
