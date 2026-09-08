<?php

namespace App\Services;

use App\Events\VentaCreada;
use App\Models\Cliente;
use App\Models\Deposito;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\Promocion;
use App\Models\Setting;
use App\Models\Venta;
use App\Support\CalculadorTotales;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Motor del punto de venta de mostrador. Concentra la lógica de una venta
 * rápida: búsqueda por código de barras, cobro multi-medio, vuelto e impacto
 * en stock y caja. Reutiliza los servicios de dominio ya existentes.
 */
class PosService
{
    public function __construct(
        private StockDocumentoService $stockDoc,
        private CajaService $caja,
        private PrecioService $precios,
    ) {}

    /**
     * Busca productos para el POS. Si `$term` matchea un código exacto
     * devuelve ese único producto (flujo de scanner); si no, lista coincidencias.
     *
     * @return array<int,array<string,mixed>>
     */
    public function buscar(string $term, int $limite = 20): array
    {
        $term = trim($term);
        if ($term === '') {
            return [];
        }

        $exacto = Producto::activos()->where('estado', 'activo')
            ->porCodigo($term)->with('codigos')->first();

        if ($exacto) {
            return [$this->serializar($exacto, $this->factorDeCodigo($exacto, $term))];
        }

        return Producto::where('estado', 'activo')
            ->where(fn ($q) => $q->where('nombre', 'like', "%{$term}%")->orWhere('codigo', 'like', "%{$term}%"))
            ->orderBy('nombre')
            ->limit($limite)
            ->get()
            ->map(fn (Producto $p) => $this->serializar($p, 1))
            ->all();
    }

    /**
     * Cotiza el carrito sin registrar nada: precios de lista, promociones y totales.
     * Lo usa el POS para mostrar el total real en vivo.
     *
     * @param  array<int,array<string,mixed>>  $items
     * @return array<string,mixed>
     */
    public function cotizar(array $items, ?int $clienteId, ?string $descuentoTipo, float $descuento): array
    {
        $cliente = $clienteId ? Cliente::find($clienteId) : null;
        $detalles = $this->construirDetalles($items, $cliente);
        $descuentoPromo = round((float) $detalles->sum('descuento_promo'), 2);

        $totales = CalculadorTotales::calcular(
            $detalles->map(fn ($d) => ['cantidad' => $d['cantidad'], 'precio' => $d['precio']])->all(),
            $descuentoTipo,
            $descuento,
            $descuentoPromo,
        );

        return [
            'lineas' => $detalles->map(fn ($d) => [
                'producto_id' => $d['producto_id'],
                'precio' => (float) $d['precio'],
                'descuento_promo' => (float) $d['descuento_promo'],
                'promocion_id' => $d['promocion_id'],
            ])->all(),
            'descuento_promo' => $descuentoPromo,
            'subtotal' => $totales['subtotal'],
            'descuento' => $totales['descuento'],
            'impuesto' => $totales['impuesto'],
            'total' => $totales['total'],
        ];
    }

    public function registrarVenta(array $data): Venta
    {
        $items = $data['items'] ?? [];
        if (empty($items)) {
            throw new RuntimeException('El carrito está vacío.');
        }

        $sesion = $this->caja->sesionAbierta(auth()->id());
        if (! $sesion) {
            throw new RuntimeException('Abrí la caja antes de vender.');
        }

        $cliente = ! empty($data['cliente_id']) ? Cliente::find($data['cliente_id']) : null;
        if ($cliente && $cliente->estado !== 'activo') {
            throw new RuntimeException('El cliente seleccionado no está activo.');
        }

        $detalles = $this->construirDetalles($items, $cliente);
        $descuentoPromo = round((float) $detalles->sum('descuento_promo'), 2);
        $totales = CalculadorTotales::calcular(
            $detalles->map(fn ($d) => ['cantidad' => $d['cantidad'], 'precio' => $d['precio']])->all(),
            $data['descuento_tipo'] ?? null,
            (float) ($data['descuento'] ?? 0),
            $descuentoPromo,
        );

        $todos = $this->normalizarPagos($data['pagos'] ?? []);
        $total = $totales['total'];

        // La "cuenta corriente" no es un cobro: es la porción que queda como deuda.
        $ccIds = MetodoPago::where('codigo', 'cuenta_corriente')->pluck('id')->all();
        $conCC = collect($todos)->contains(fn ($p) => in_array($p['metodo_pago_id'], $ccIds, true));
        $pagos = array_values(array_filter($todos, fn ($p) => ! in_array($p['metodo_pago_id'], $ccIds, true)));

        if ($conCC && ! $cliente) {
            throw new RuntimeException('Cuenta corriente requiere un cliente.');
        }

        $efectivoPunteado = $this->totalEfectivo($pagos);            // líneas de efectivo tipeadas
        $noEfectivo = round(array_sum(array_column($pagos, 'monto')) - $efectivoPunteado, 2);
        $efectivoNecesario = max(0, round($total - $noEfectivo, 2)); // lo que el efectivo debe cubrir

        // `recibido` = efectivo físico entregado por el cliente. Si no viene,
        // se asume que entregó exactamente lo punteado en las líneas de efectivo.
        $entregado = round((float) ($data['recibido'] ?? 0), 2);
        $efectivoEntregado = max($entregado, $efectivoPunteado);

        if (! $conCC && round($efectivoEntregado + $noEfectivo, 2) + 0.01 < $total) {
            throw new RuntimeException('El pago no cubre el total ($'.number_format($total, 2).').');
        }

        $vuelto = round(max(0, $efectivoEntregado - $efectivoNecesario), 2);
        $recibido = $efectivoEntregado;
        // Efectivo que realmente entra a la venta (sin el vuelto).
        $efectivoRegistrado = round(min($efectivoEntregado, $efectivoNecesario), 2);
        // Lo efectivamente cobrado (sin la parte fiada).
        $pagadoReal = min($total, round($noEfectivo + $efectivoRegistrado, 2));

        $pagos = $this->consolidarEfectivo($pagos, $efectivoRegistrado);

        if ($cliente && $conCC) {
            $this->validarCredito($cliente, $total, $pagadoReal);
        }

        $venta = DB::transaction(function () use ($data, $cliente, $sesion, $detalles, $totales, $pagos, $recibido, $vuelto, $pagadoReal) {
            $venta = Venta::create([
                'numero' => $this->generarNumero(),
                'cliente_id' => $cliente?->id,
                'deposito_id' => Deposito::principalId(),
                'caja_sesion_id' => $sesion->id,
                'fecha' => now()->toDateString(),
                'subtotal' => $totales['subtotal'],
                'descuento' => $totales['descuento'],
                'descuento_tipo' => $data['descuento_tipo'] ?? null,
                'impuesto' => $totales['impuesto'],
                'total_final' => $totales['total'],
                'total' => $totales['total'],
                'pagado' => min($pagadoReal, $totales['total']),
                'recibido' => $recibido,
                'vuelto' => $vuelto,
                'estado_pago' => CalculadorTotales::estadoPago($totales['total'], $pagadoReal),
                'estado' => 'completada',
                'canal' => 'mostrador',
                'user_id' => auth()->id(),
            ]);

            $venta->detalles()->createMany($detalles->all());

            foreach ($pagos as $p) {
                $venta->pagos()->create([
                    'metodo_pago_id' => $p['metodo_pago_id'],
                    'monto' => $p['monto'],
                    'referencia' => $p['referencia'] ?? null,
                ]);
            }

            $this->stockDoc->aplicarVenta($venta);

            return $venta;
        });

        // El efectivo que entra a la caja es el neto (ya sin el vuelto).
        $efectivoNeto = $this->totalEfectivo($pagos);
        if ($efectivoNeto > 0) {
            $this->caja->registrarMovimiento('ingreso', "Venta {$venta->numero}", $efectivoNeto, 'venta', $venta->id);
        }

        VentaCreada::dispatch($venta);

        return $venta->load('detalles.producto', 'pagos.metodoPago', 'cliente');
    }

    /** @param array<int,array<string,mixed>> $items */
    private function construirDetalles(array $items, ?Cliente $cliente = null): Collection
    {
        $ids = collect($items)->pluck('producto_id');
        $productos = Producto::with('preciosLista')->whereIn('id', $ids)->get()->keyBy('id');
        $listaId = $this->precios->listaDe($cliente);
        $catalogoPromos = Promocion::vigentes()->get();
        $ahora = now();

        return collect($items)->map(function ($item) use ($productos, $listaId, $catalogoPromos, $ahora) {
            $producto = $productos->get($item['producto_id']);
            if (! $producto) {
                throw new RuntimeException('Producto inexistente en el carrito.');
            }
            $cantidad = round((float) $item['cantidad'], 3);
            if ($cantidad <= 0) {
                throw new RuntimeException("Cantidad inválida para «{$producto->nombre}».");
            }

            $manual = ! empty($item['precio_manual']);
            $precio = $manual
                ? round((float) $item['precio'], 2)
                : $this->precios->precioBase($producto, $listaId);

            $descPromo = 0.0;
            $promoId = null;
            if (! $manual) {
                $promo = $this->precios->mejorPromocion($producto, $cantidad, $precio, $ahora, $catalogoPromos);
                if ($promo) {
                    $descPromo = $promo['descuento'];
                    $promoId = $promo['promocion']->id;
                }
            }

            return [
                'producto_id' => $producto->id,
                'cantidad' => $cantidad,
                'precio' => $precio,
                'costo_unitario' => round((float) $producto->precio_compra, 2),
                'subtotal' => round($cantidad * $precio - $descPromo, 2),
                'descuento_promo' => round($descPromo, 2),
                'promocion_id' => $promoId,
            ];
        });
    }

    /**
     * @param  array<int,array<string,mixed>>  $pagos
     * @return array<int,array{metodo_pago_id:int,monto:float,referencia:string|null}>
     */
    private function normalizarPagos(array $pagos): array
    {
        $salida = [];
        foreach ($pagos as $p) {
            $monto = round((float) ($p['monto'] ?? 0), 2);
            if (empty($p['metodo_pago_id']) || $monto <= 0) {
                continue;
            }
            $salida[] = [
                'metodo_pago_id' => (int) $p['metodo_pago_id'],
                'monto' => $monto,
                'referencia' => $p['referencia'] ?? null,
            ];
        }
        if (empty($salida)) {
            throw new RuntimeException('Registrá al menos un medio de pago.');
        }

        return $salida;
    }

    /**
     * Reemplaza las líneas de efectivo por una sola de monto `$objetivo`
     * (el efectivo que realmente entra a la venta, sin el vuelto).
     *
     * @param  array<int,array<string,mixed>>  $pagos
     * @return array<int,array<string,mixed>>
     */
    private function consolidarEfectivo(array $pagos, float $objetivo): array
    {
        $efectivoIds = MetodoPago::where('codigo', 'efectivo')->pluck('id')->all();
        $sinEfectivo = array_values(array_filter($pagos, fn ($p) => ! in_array($p['metodo_pago_id'], $efectivoIds, true)));

        if ($objetivo <= 0.001) {
            return $sinEfectivo ?: $pagos;
        }

        $metodoEfectivo = collect($pagos)->firstWhere(fn ($p) => in_array($p['metodo_pago_id'], $efectivoIds, true));

        return array_merge($sinEfectivo, [[
            'metodo_pago_id' => $metodoEfectivo['metodo_pago_id'] ?? ($efectivoIds[0] ?? null),
            'monto' => round($objetivo, 2),
            'referencia' => null,
        ]]);
    }

    /** @param array<int,array<string,mixed>> $pagos */
    private function totalEfectivo(array $pagos): float
    {
        $ids = MetodoPago::where('codigo', 'efectivo')->pluck('id')->all();

        return round(collect($pagos)
            ->filter(fn ($p) => in_array($p['metodo_pago_id'], $ids, true))
            ->sum('monto'), 2);
    }

    /** @param array<int,array<string,mixed>> $pagos */
    private function tieneCuentaCorriente(array $pagos): bool
    {
        $ids = MetodoPago::where('codigo', 'cuenta_corriente')->pluck('id')->all();

        return collect($pagos)->contains(fn ($p) => in_array($p['metodo_pago_id'], $ids, true));
    }

    private function validarCredito(Cliente $cliente, float $total, float $pagado): void
    {
        $limite = (float) $cliente->limite_credito;
        if ($limite <= 0) {
            return;
        }
        $nuevoSaldo = max(0, $total - $pagado);
        $proyectado = $cliente->saldo() + $nuevoSaldo;
        if ($proyectado > $limite + 0.01) {
            throw new RuntimeException('Supera el límite de crédito del cliente ($'.number_format($limite, 2).').');
        }
    }

    private function factorDeCodigo(Producto $producto, string $codigo): float
    {
        $c = $producto->codigos->firstWhere('codigo', $codigo);

        return $c ? (float) $c->factor : 1.0;
    }

    private function serializar(Producto $producto, float $factor): array
    {
        return [
            'id' => $producto->id,
            'nombre' => $producto->nombre,
            'codigo' => $producto->codigo,
            'precio' => (float) $producto->precio_venta,
            'stock' => (int) $producto->stock,
            'es_pesable' => (bool) $producto->es_pesable,
            'factor' => $factor,
        ];
    }

    private function generarNumero(): string
    {
        $prefijo = Setting::obtener('ventas_prefijo_numero', 'VTA');
        $digitos = (int) Setting::obtener('ventas_cantidad_digitos', '5');

        $ultima = Venta::where('numero', 'like', "{$prefijo}-%")
            ->orderByRaw('CAST(SUBSTRING(numero, '.(strlen($prefijo) + 2).') AS UNSIGNED) DESC')
            ->lockForUpdate()
            ->value('numero');

        $n = $ultima ? ((int) substr($ultima, strlen($prefijo) + 1)) + 1 : 1;

        return $prefijo.'-'.str_pad((string) $n, $digitos, '0', STR_PAD_LEFT);
    }
}
