<?php

namespace App\Services;

use App\Exceptions\ReglaDeNegocioException;
use App\Exceptions\StockInsuficienteException;
use App\Models\Compra;
use App\Models\Devolucion;
use App\Models\DevolucionDetalle;
use App\Models\Producto;
use App\Models\Venta;
use App\Support\Mongo\Bloqueo;
use App\Support\Mongo\Transaccion;
use App\Support\NumeradorDocumentos;

/**
 * Devoluciones de ventas (el cliente devuelve) y de compras (se devuelve al proveedor).
 *
 * Todo el control corre ADENTRO de la transacción y contra el documento releído con
 * un candado (ver Bloqueo): el estado, el stock aplicado y lo ya devuelto se miran
 * en el mismo instante en que se escribe.
 */
class DevolucionService
{
    public function __construct(private StockService $stock, private CajaService $caja) {}

    /**
     * @param  array<int,array{producto_id:mixed,cantidad:mixed}>  $pedidas
     *
     * @throws ReglaDeNegocioException
     * @throws StockInsuficienteException
     */
    public function devolverVenta(Venta $venta, array $pedidas, ?string $motivo, bool $reembolsoEfectivo, ?int $userId): Devolucion
    {
        return Transaccion::en(function () use ($venta, $pedidas, $motivo, $reembolsoEfectivo, $userId) {
            Bloqueo::tomar($venta, ['estado' => 'completada', 'stock_aplicado' => true], 'La venta no se puede devolver: no está completada o nunca movió stock.');

            $lineas = $this->validar($venta->detalles()->with('producto')->get(), $this->devueltosPorProducto('venta', $venta->id), $pedidas, 'la venta');

            if ($reembolsoEfectivo) {
                $this->caja->exigirSesionPropia($userId, 'reembolso_efectivo');
            }

            $devolucion = Devolucion::create([
                'numero'   => NumeradorDocumentos::proximo('devoluciones', 'DEV'),
                'tipo'     => 'venta',
                'venta_id' => $venta->id,
                'fecha'    => now()->toDateString(),
                'motivo'   => $motivo,
                'total'    => round(collect($lineas)->sum('subtotal'), 2),
                'estado'   => 'registrada',
                'user_id'  => $userId,
            ]);

            foreach ($lineas as $l) {
                $devolucion->detalles()->create($l);
                $this->stock->registrarDevolucion(Producto::find($l['producto_id']), $l['cantidad'], 'devolucion_venta', $devolucion->id);
            }

            if ($reembolsoEfectivo) {
                $this->caja->registrarMovimiento('egreso', "Reembolso devolución {$devolucion->numero}", (float) $devolucion->total, 'devolucion', $devolucion->id);
            }

            return $devolucion;
        });
    }

    /**
     * @param  array<int,array{producto_id:mixed,cantidad:mixed}>  $pedidas
     *
     * @throws ReglaDeNegocioException
     * @throws StockInsuficienteException
     */
    public function devolverCompra(Compra $compra, array $pedidas, ?string $motivo, ?int $userId): Devolucion
    {
        return Transaccion::en(function () use ($compra, $pedidas, $motivo, $userId) {
            Bloqueo::tomar($compra, ['estado' => 'completada', 'stock_aplicado' => true], 'La compra no se puede devolver: no está completada o nunca movió stock.');

            $lineas = $this->validar($compra->detalles()->with('producto')->get(), $this->devueltosPorProducto('compra', $compra->id), $pedidas, 'la compra');

            $devolucion = Devolucion::create([
                'numero'    => NumeradorDocumentos::proximo('devoluciones', 'DEV'),
                'tipo'      => 'compra',
                'compra_id' => $compra->id,
                'fecha'     => now()->toDateString(),
                'motivo'    => $motivo,
                'total'     => round(collect($lineas)->sum('subtotal'), 2),
                'estado'    => 'registrada',
                'user_id'   => $userId,
            ]);

            foreach ($lineas as $l) {
                $devolucion->detalles()->create($l);
                $this->stock->registrarSalida(Producto::find($l['producto_id']), $l['cantidad'], 'devolucion_compra', $devolucion->id);
            }

            return $devolucion;
        });
    }

    /** @return array<int,int> producto_id => cantidad ya devuelta */
    public function devueltosPorProducto(string $tipo, int $docId): array
    {
        $col = $tipo === 'venta' ? 'venta_id' : 'compra_id';

        $ids = Devolucion::where('tipo', $tipo)->where($col, $docId)->where('estado', 'registrada')->pluck('id')->all();

        return DevolucionDetalle::whereIn('devolucion_id', $ids)
            ->get()
            ->groupBy('producto_id')
            ->map(fn ($lineas) => (int) $lineas->sum('cantidad'))
            ->all();
    }

    /**
     * @param  iterable<int,mixed>  $originales
     * @param  array<int,int>  $devueltos
     * @param  array<int,array{producto_id:mixed,cantidad:mixed}>  $pedidas
     * @return list<array{producto_id:mixed,cantidad:int,precio:float,subtotal:float}>
     *
     * @throws ReglaDeNegocioException
     */
    private function validar(iterable $originales, array $devueltos, array $pedidas, string $documento): array
    {
        $originales = collect($originales);
        $lineas = [];

        foreach ($pedidas as $d) {
            $cantidad = (int) $d['cantidad'];
            if ($cantidad <= 0) {
                continue;
            }

            $original = $originales->firstWhere('producto_id', (int) $d['producto_id']);
            if (! $original) {
                throw new ReglaDeNegocioException("Un producto no pertenece a {$documento}.", 'detalles');
            }

            $disponible = $original->cantidad - ($devueltos[$original->producto_id] ?? 0);
            if ($cantidad > $disponible) {
                throw new ReglaDeNegocioException("No podés devolver más de {$disponible} de «{$original->producto->nombre}».", 'detalles');
            }

            $lineas[] = [
                'producto_id' => $original->producto_id,
                'cantidad'    => $cantidad,
                'precio'      => (float) $original->precio,
                'subtotal'    => round($cantidad * $original->precio, 2),
            ];
        }

        if (empty($lineas)) {
            throw new ReglaDeNegocioException('Ingresá al menos una cantidad a devolver.', 'detalles');
        }

        return $lineas;
    }
}
