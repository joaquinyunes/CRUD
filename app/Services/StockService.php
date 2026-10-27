<?php

namespace App\Services;

use App\Events\StockBajo;
use App\Exceptions\StockInsuficienteException;
use App\Models\Deposito;
use App\Models\MovimientoStock;
use App\Models\Producto;
use App\Models\Setting;
use App\Support\Mongo\StockDeposito;
use App\Support\Mongo\Transaccion;

/**
 * Libro de movimientos + saldo por depósito.
 *
 * En SQL el saldo se leía con lockForUpdate. En MongoDB no hay bloqueo de fila:
 * la salida es un único findOneAndUpdate atómico ($inc solo si alcanza el
 * stock), así que dos ventas simultáneas no pueden dejar el saldo negativo.
 */
class StockService
{
    public function registrarSalida(Producto $producto, int $cantidad, string $referenciaTipo, ?int $referenciaId, ?int $depositoId = null): void
    {
        $this->validarCantidad($cantidad);
        $depositoId ??= Deposito::principalId();

        Transaccion::en(function () use ($producto, $cantidad, $referenciaTipo, $referenciaId, $depositoId) {
            $minimo = $this->permiteNegativo() ? null : 0;

            if (StockDeposito::sumar($producto->id, $depositoId, -$cantidad, $minimo) === null) {
                $disponible = StockDeposito::cantidad($producto->id, $depositoId);

                throw new StockInsuficienteException(
                    "Stock insuficiente para «{$producto->nombre}» en el depósito. Disponible: {$disponible}, solicitado: {$cantidad}."
                );
            }

            $this->movimiento($producto->id, $depositoId, 'salida', $cantidad, $referenciaTipo, $referenciaId);
            $this->ajustarTotal($producto, -$cantidad);
        });

        if ($producto->stock <= $producto->stock_minimo) {
            StockBajo::dispatch($producto);
        }
    }

    public function registrarEntrada(Producto $producto, int $cantidad, string $referenciaTipo, ?int $referenciaId, ?int $depositoId = null): void
    {
        $this->validarCantidad($cantidad);
        $depositoId ??= Deposito::principalId();

        Transaccion::en(function () use ($producto, $cantidad, $referenciaTipo, $referenciaId, $depositoId) {
            StockDeposito::sumar($producto->id, $depositoId, $cantidad);
            $this->movimiento($producto->id, $depositoId, 'entrada', $cantidad, $referenciaTipo, $referenciaId);
            $this->ajustarTotal($producto, $cantidad);
        });
    }

    public function registrarDevolucion(Producto $producto, int $cantidad, string $referenciaTipo, ?int $referenciaId, ?int $depositoId = null): void
    {
        $this->validarCantidad($cantidad);
        $depositoId ??= Deposito::principalId();

        Transaccion::en(function () use ($producto, $cantidad, $referenciaTipo, $referenciaId, $depositoId) {
            StockDeposito::sumar($producto->id, $depositoId, $cantidad);
            $this->movimiento($producto->id, $depositoId, 'devolucion', $cantidad, $referenciaTipo, $referenciaId);
            $this->ajustarTotal($producto, $cantidad);
        });
    }

    public function registrarAjuste(Producto $producto, int $cantidadNueva, string $motivo, ?int $depositoId = null): void
    {
        $depositoId ??= Deposito::principalId();

        Transaccion::en(function () use ($producto, $cantidadNueva, $motivo, $depositoId) {
            $actual = StockDeposito::reemplazar($producto->id, $depositoId, $cantidadNueva);
            $diferencia = $cantidadNueva - $actual;

            $this->movimiento($producto->id, $depositoId, 'ajuste', abs($diferencia), $motivo, null);
            $this->ajustarTotal($producto, $diferencia);
        });
    }

    /**
     * Mueve stock de un depósito a otro (dos movimientos, sin tocar el total).
     */
    public function transferir(Producto $producto, int $cantidad, int $origenId, int $destinoId): void
    {
        $this->validarCantidad($cantidad);

        if ($origenId === $destinoId) {
            throw new \InvalidArgumentException('El origen y el destino deben ser distintos.');
        }

        Transaccion::en(function () use ($producto, $cantidad, $origenId, $destinoId) {
            if (StockDeposito::sumar($producto->id, $origenId, -$cantidad, 0) === null) {
                $disponible = StockDeposito::cantidad($producto->id, $origenId);

                throw new StockInsuficienteException(
                    "Stock insuficiente en el depósito de origen para «{$producto->nombre}». Disponible: {$disponible}."
                );
            }
            StockDeposito::sumar($producto->id, $destinoId, $cantidad);

            $this->movimiento($producto->id, $origenId, 'transferencia_salida', $cantidad, 'transferencia', $destinoId);
            $this->movimiento($producto->id, $destinoId, 'transferencia_entrada', $cantidad, 'transferencia', $origenId);
            // El total del producto no cambia en una transferencia.
        });
    }

    // ---- internos ----

    /** Mantiene productos.stock como total denormalizado (lo usan reportes y alertas). */
    private function ajustarTotal(Producto $producto, int $delta): void
    {
        Producto::whereKey($producto->id)->increment('stock', $delta);

        // Se lee de la base y no se suma en memoria: si la transacción se reintenta, no se duplica.
        $producto->stock = (int) Producto::whereKey($producto->id)->value('stock');
    }

    private function movimiento(int $productoId, int $depositoId, string $tipo, int $cantidad, string $referenciaTipo, ?int $referenciaId): void
    {
        MovimientoStock::create([
            'producto_id'     => $productoId,
            'deposito_id'     => $depositoId,
            'tipo'            => $tipo,
            'cantidad'        => $cantidad,
            'user_id'         => auth()->id(),
            'referencia_tipo' => $referenciaTipo,
            'referencia_id'   => $referenciaId,
        ]);
    }

    private function permiteNegativo(): bool
    {
        return Setting::obtener('stock_permitir_negativo', '0') === '1';
    }

    private function validarCantidad(int $cantidad): void
    {
        if ($cantidad <= 0) {
            throw new \InvalidArgumentException('La cantidad debe ser mayor a 0.');
        }
    }
}
