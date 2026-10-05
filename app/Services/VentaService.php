<?php

namespace App\Services;

use App\Events\VentaCreada;
use App\Exceptions\ReglaDeNegocioException;
use App\Exceptions\StockInsuficienteException;
use App\Models\Cliente;
use App\Models\Deposito;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\Setting;
use App\Models\Venta;
use App\Support\CalculadorTotales;
use App\Support\NumeradorDocumentos;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Emitir una venta: el único lugar donde se crea una.
 *
 * Existía dos veces, una en el controller web y otra en el de la API, y las dos
 * copias ya habían divergido: la de la API sacaba el número del comprobante de
 * MAX(id) + 1 —sin bloqueo, ignorando el prefijo configurado y reutilizando
 * números al borrar una venta— e ignoraba el descuento, el depósito y los
 * medios de pago. Una venta cargada por API y otra por pantalla podían salir
 * con el mismo número.
 */
class VentaService
{
    public function __construct(
        private StockDocumentoService $stockDoc,
        private CajaService $caja,
    ) {}

    /**
     * @param  array<string,mixed>  $datos
     *
     * @throws ReglaDeNegocioException
     * @throws StockInsuficienteException
     */
    public function crear(array $datos, ?int $autorId = null): Venta
    {
        $cliente = Cliente::findOrFail($datos['cliente_id']);

        if ($cliente->estado !== 'activo') {
            throw new ReglaDeNegocioException('El cliente seleccionado no está activo.', 'cliente_id');
        }

        $pagos = $datos['metodos_pago'] ?? [];
        $detalles = $this->construirDetalles($datos['detalles']);
        $totales = CalculadorTotales::calcular(
            $detalles->all(),
            $datos['descuento_tipo'] ?? null,
            (float) ($datos['descuento'] ?? 0)
        );

        $this->verificarCredito($cliente, $totales['total'], $pagos);

        $venta = DB::transaction(function () use ($datos, $detalles, $totales, $pagos, $autorId) {
            $venta = Venta::create([
                'numero'         => $this->proximoNumero(),
                'cliente_id'     => $datos['cliente_id'],
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

            $venta->detalles()->createMany($detalles->all());
            $this->sincronizarPagos($venta, $pagos, $totales['total']);

            if ($venta->estado === 'completada') {
                $this->stockDoc->aplicarVenta($venta);
            }

            return $venta;
        });

        // Fuera de la transacción: la caja es un documento aparte, y el aviso no
        // tiene por qué demorar el cierre de la venta.
        $this->registrarEfectivoEnCaja($venta, $pagos);

        VentaCreada::dispatch($venta);

        return $venta;
    }

    /**
     * Reescribe una venta ya emitida.
     *
     * Conserva su número y su autor: lo que cambia es el contenido. El stock del
     * detalle anterior se devuelve antes de aplicar el nuevo, y la caja se
     * ajusta solo por la diferencia de efectivo.
     *
     * @param  array<string,mixed>  $datos
     *
     * @throws ReglaDeNegocioException
     * @throws StockInsuficienteException
     */
    public function actualizar(Venta $venta, array $datos): Venta
    {
        $cliente = Cliente::findOrFail($datos['cliente_id']);

        if ($cliente->estado !== 'activo') {
            throw new ReglaDeNegocioException('El cliente seleccionado no está activo.', 'cliente_id');
        }

        $pagos = $datos['metodos_pago'] ?? [];
        $efectivoAnterior = $this->efectivoRegistradoEn($venta);

        DB::transaction(function () use ($venta, $datos, $pagos) {
            $this->stockDoc->revertirVenta($venta);

            $detalles = $this->construirDetalles($datos['detalles']);
            $totales = CalculadorTotales::calcular(
                $detalles->all(),
                $datos['descuento_tipo'] ?? null,
                (float) ($datos['descuento'] ?? 0)
            );

            $venta->update([
                'cliente_id'     => $datos['cliente_id'],
                'deposito_id'    => ($datos['deposito_id'] ?? null) ?: $venta->deposito_id,
                'fecha'          => $datos['fecha'],
                'subtotal'       => $totales['subtotal'],
                'descuento'      => $totales['descuento'],
                'descuento_tipo' => $datos['descuento_tipo'] ?? null,
                'impuesto'       => $totales['impuesto'],
                'total_final'    => $totales['total'],
                'total'          => $totales['total'],
                'estado'         => $datos['estado'],
            ]);

            $venta->detalles()->delete();
            $venta->detalles()->createMany($detalles->all());
            $this->sincronizarPagos($venta, $pagos, $totales['total']);

            if ($venta->estado === 'completada') {
                $this->stockDoc->aplicarVenta($venta);
            }
        });

        $this->ajustarEfectivoEnCaja($venta, $efectivoAnterior, $pagos);

        return $venta;
    }

    /**
     * Las líneas, con el costo del producto congelado al momento de emitir (lo
     * usa el reporte de rentabilidad: si mañana cambia el precio de compra, la
     * ganancia de esta venta no se mueve).
     *
     * @param  array<int,array<string,mixed>>  $detalles
     * @return Collection<int,array{producto_id:mixed,cantidad:int,precio:float,costo_unitario:float,subtotal:float}>
     */
    public function construirDetalles(array $detalles): Collection
    {
        $costos = Producto::whereIn('id', collect($detalles)->pluck('producto_id'))
            ->pluck('precio_compra', 'id');

        return collect($detalles)->map(function (array $item) use ($costos): array {
            $cantidad = (int) $item['cantidad'];
            $precio = round((float) $item['precio'], 2);

            return [
                'producto_id'    => $item['producto_id'],
                'cantidad'       => $cantidad,
                'precio'         => $precio,
                'costo_unitario' => round((float) ($costos[$item['producto_id']] ?? 0), 2),
                'subtotal'       => round($cantidad * $precio, 2),
            ];
        });
    }

    /**
     * Reemplaza los pagos del comprobante y recalcula su estado de pago.
     *
     * @param  array<int,array<string,mixed>>  $pagos
     */
    public function sincronizarPagos(Venta $venta, array $pagos, float $total): void
    {
        $venta->pagos()->delete();
        $pagado = 0.0;

        foreach ($pagos as $pago) {
            $monto = round((float) ($pago['monto'] ?? 0), 2);

            if (empty($pago['metodo_pago_id']) || $monto <= 0) {
                continue;
            }

            $venta->pagos()->create([
                'metodo_pago_id' => $pago['metodo_pago_id'],
                'monto'          => $monto,
                'referencia'     => $pago['referencia'] ?? null,
            ]);

            $pagado += $monto;
        }

        $venta->update([
            'pagado'      => $pagado,
            'estado_pago' => CalculadorTotales::estadoPago($total, $pagado),
        ]);
    }

    /** @param  array<int,array<string,mixed>>  $pagos */
    public function registrarEfectivoEnCaja(Venta $venta, array $pagos): void
    {
        $monto = $this->efectivoDe($pagos);

        if ($monto > 0) {
            $this->caja->registrarMovimiento('ingreso', "Venta {$venta->numero}", $monto, 'venta', $venta->id);
        }
    }

    /** @param  array<int,array<string,mixed>>  $pagos */
    public function efectivoDe(array $pagos): float
    {
        $efectivoIds = MetodoPago::where('codigo', 'efectivo')->pluck('id')->all();

        return (float) collect($pagos)
            ->filter(fn ($pago) => in_array((int) ($pago['metodo_pago_id'] ?? 0), $efectivoIds, true))
            ->sum(fn ($pago) => (float) ($pago['monto'] ?? 0));
    }

    /** El efectivo que la venta ya tiene imputado, para ajustar la caja al editarla. */
    public function efectivoRegistradoEn(Venta $venta): float
    {
        $efectivoIds = MetodoPago::where('codigo', 'efectivo')->pluck('id')->all();

        return (float) $venta->pagos()->whereIn('metodo_pago_id', $efectivoIds)->sum('monto');
    }

    /**
     * Mueve en la caja únicamente la diferencia de efectivo tras editar.
     *
     * @param  array<int,array<string,mixed>>  $pagosNuevos
     */
    public function ajustarEfectivoEnCaja(Venta $venta, float $efectivoAnterior, array $pagosNuevos): void
    {
        $delta = round($this->efectivoDe($pagosNuevos) - $efectivoAnterior, 2);

        if (abs($delta) < 0.01) {
            return;
        }

        $this->caja->registrarMovimiento(
            $delta > 0 ? 'ingreso' : 'egreso',
            "Ajuste edición venta {$venta->numero}",
            abs($delta),
            'venta',
            $venta->id
        );
    }

    public function proximoNumero(): string
    {
        return NumeradorDocumentos::proximo(
            'ventas',
            Setting::obtener('ventas_prefijo_numero', 'VTA'),
            (int) Setting::obtener('ventas_cantidad_digitos', '5'),
        );
    }

    /**
     * @param  array<int,array<string,mixed>>  $pagos
     *
     * @throws ReglaDeNegocioException
     */
    private function verificarCredito(Cliente $cliente, float $total, array $pagos): void
    {
        $limite = (float) $cliente->limite_credito;

        if ($limite <= 0) {
            return;
        }

        $aPagar = collect($pagos)->sum(fn ($pago) => (float) ($pago['monto'] ?? 0));
        $proyectado = $cliente->saldo() + max(0, $total - $aPagar);

        if ($proyectado > $limite + 0.01) {
            throw new ReglaDeNegocioException(
                'Supera el límite de crédito del cliente ($'.number_format($limite, 2)
                .'). Deuda proyectada: $'.number_format($proyectado, 2).'.',
                'cliente_id'
            );
        }
    }
}
