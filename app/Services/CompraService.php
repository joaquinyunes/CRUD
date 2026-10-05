<?php

namespace App\Services;

use App\Events\CompraCreada;
use App\Exceptions\StockInsuficienteException;
use App\Models\Compra;
use App\Models\Deposito;
use App\Models\Producto;
use App\Models\Setting;
use App\Support\CalculadorTotales;
use App\Support\NumeradorDocumentos;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Registrar una compra: el único lugar donde se crea una.
 *
 * Igual que con las ventas, estaba implementado dos veces y la copia de la API
 * numeraba los comprobantes con MAX(id) + 1, sin bloqueo y sin respetar el
 * prefijo configurado.
 */
class CompraService
{
    public function __construct(
        private StockDocumentoService $stockDoc,
    ) {}

    /**
     * @param  array<string,mixed>  $datos
     *
     * @throws StockInsuficienteException
     */
    public function crear(array $datos, ?int $autorId = null): Compra
    {
        $pagos = $datos['metodos_pago'] ?? [];
        $detalles = $this->construirDetalles($datos['detalles']);
        $totales = CalculadorTotales::calcular(
            $detalles->all(),
            $datos['descuento_tipo'] ?? null,
            (float) ($datos['descuento'] ?? 0)
        );

        $compra = DB::transaction(function () use ($datos, $detalles, $totales, $pagos, $autorId) {
            $compra = Compra::create([
                'numero'         => $this->proximoNumero(),
                'proveedor_id'   => $datos['proveedor_id'],
                'deposito_id'    => ($datos['deposito_id'] ?? null) ?: Deposito::principalId(),
                'fecha'          => $datos['fecha'],
                'subtotal'       => $totales['subtotal'],
                'descuento'      => $totales['descuento'],
                'descuento_tipo' => $datos['descuento_tipo'] ?? null,
                'impuesto'       => $totales['impuesto'],
                'total_final'    => $totales['total'],
                'total'          => $totales['total'],
                'estado'         => $datos['estado'],
                'user_id'        => $autorId ?? auth()->id(),
            ]);

            $compra->detalles()->createMany($detalles->all());
            $this->sincronizarPagos($compra, $pagos, $totales['total']);

            if ($compra->estado === 'completada') {
                $this->stockDoc->aplicarCompra($compra);
                $this->actualizarCostos($detalles->all());
            }

            return $compra;
        });

        CompraCreada::dispatch($compra);

        return $compra;
    }

    /**
     * Reescribe una compra ya registrada, conservando su número y su autor.
     *
     * El stock del detalle anterior se descuenta antes de aplicar el nuevo.
     *
     * @param  array<string,mixed>  $datos
     *
     * @throws StockInsuficienteException
     */
    public function actualizar(Compra $compra, array $datos): Compra
    {
        DB::transaction(function () use ($compra, $datos) {
            $this->stockDoc->revertirCompra($compra);

            $detalles = $this->construirDetalles($datos['detalles']);
            $totales = CalculadorTotales::calcular(
                $detalles->all(),
                $datos['descuento_tipo'] ?? null,
                (float) ($datos['descuento'] ?? 0)
            );

            $compra->update([
                'proveedor_id'   => $datos['proveedor_id'],
                'deposito_id'    => ($datos['deposito_id'] ?? null) ?: $compra->deposito_id,
                'fecha'          => $datos['fecha'],
                'subtotal'       => $totales['subtotal'],
                'descuento'      => $totales['descuento'],
                'descuento_tipo' => $datos['descuento_tipo'] ?? null,
                'impuesto'       => $totales['impuesto'],
                'total_final'    => $totales['total'],
                'total'          => $totales['total'],
                'estado'         => $datos['estado'],
            ]);

            $compra->detalles()->delete();
            $compra->detalles()->createMany($detalles->all());
            $this->sincronizarPagos($compra, $datos['metodos_pago'] ?? [], $totales['total']);

            if ($compra->estado === 'completada') {
                $this->stockDoc->aplicarCompra($compra);
                $this->actualizarCostos($detalles->all());
            }
        });

        return $compra;
    }

    /**
     * @param  array<int,array<string,mixed>>  $detalles
     * @return Collection<int,array{producto_id:mixed,cantidad:int,precio:float,subtotal:float}>
     */
    public function construirDetalles(array $detalles): Collection
    {
        return collect($detalles)->map(function (array $item): array {
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
     * Reemplaza los pagos del comprobante y recalcula su estado de pago.
     *
     * @param  array<int,array<string,mixed>>  $pagos
     */
    public function sincronizarPagos(Compra $compra, array $pagos, float $total): void
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

    /**
     * El último precio pagado pasa a ser el costo del producto: es lo que
     * alimenta el margen de los reportes.
     *
     * @param  array<int,array<string,mixed>>  $detalles
     */
    public function actualizarCostos(array $detalles): void
    {
        foreach ($detalles as $detalle) {
            Producto::whereKey($detalle['producto_id'])->update(['precio_compra' => $detalle['precio']]);
        }
    }

    public function proximoNumero(): string
    {
        return NumeradorDocumentos::proximo(
            'compras',
            Setting::obtener('compras_prefijo_numero', 'COM'),
            (int) Setting::obtener('compras_cantidad_digitos', '5'),
        );
    }
}
