<?php

namespace App\Services;

use App\Events\StockBajo;
use App\Exceptions\StockInsuficienteException;
use App\Models\Deposito;
use App\Models\MovimientoStock;
use App\Models\Producto;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function registrarSalida(Producto $producto, int $cantidad, string $referenciaTipo, ?int $referenciaId, ?int $depositoId = null): void
    {
        $this->validarCantidad($cantidad);
        $depositoId ??= Deposito::principalId();

        DB::transaction(function () use ($producto, $cantidad, $referenciaTipo, $referenciaId, $depositoId) {
            $disponible = $this->stockBloqueado($producto->id, $depositoId);

            if (! $this->permiteNegativo() && $disponible < $cantidad) {
                throw new StockInsuficienteException(
                    "Stock insuficiente para «{$producto->nombre}» en el depósito. Disponible: {$disponible}, solicitado: {$cantidad}."
                );
            }

            $this->movimiento($producto->id, $depositoId, 'salida', $cantidad, $referenciaTipo, $referenciaId);
            $this->ajustar($producto, $depositoId, -$cantidad);
        });

        if ($producto->stock <= $producto->stock_minimo) {
            StockBajo::dispatch($producto);
        }
    }

    public function registrarEntrada(Producto $producto, int $cantidad, string $referenciaTipo, ?int $referenciaId, ?int $depositoId = null): void
    {
        $this->validarCantidad($cantidad);
        $depositoId ??= Deposito::principalId();

        DB::transaction(function () use ($producto, $cantidad, $referenciaTipo, $referenciaId, $depositoId) {
            $this->stockBloqueado($producto->id, $depositoId);
            $this->movimiento($producto->id, $depositoId, 'entrada', $cantidad, $referenciaTipo, $referenciaId);
            $this->ajustar($producto, $depositoId, $cantidad);
        });
    }

    public function registrarDevolucion(Producto $producto, int $cantidad, string $referenciaTipo, ?int $referenciaId, ?int $depositoId = null): void
    {
        $this->validarCantidad($cantidad);
        $depositoId ??= Deposito::principalId();

        DB::transaction(function () use ($producto, $cantidad, $referenciaTipo, $referenciaId, $depositoId) {
            $this->stockBloqueado($producto->id, $depositoId);
            $this->movimiento($producto->id, $depositoId, 'devolucion', $cantidad, $referenciaTipo, $referenciaId);
            $this->ajustar($producto, $depositoId, $cantidad);
        });
    }

    public function registrarAjuste(Producto $producto, int $cantidadNueva, string $motivo, ?int $depositoId = null): void
    {
        $depositoId ??= Deposito::principalId();

        DB::transaction(function () use ($producto, $cantidadNueva, $motivo, $depositoId) {
            $actual = $this->stockBloqueado($producto->id, $depositoId);
            $diferencia = $cantidadNueva - $actual;

            $this->movimiento($producto->id, $depositoId, 'ajuste', abs($diferencia), $motivo, null);
            $this->ajustar($producto, $depositoId, $diferencia);
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

        DB::transaction(function () use ($producto, $cantidad, $origenId, $destinoId) {
            $disponible = $this->stockBloqueado($producto->id, $origenId);
            if ($disponible < $cantidad) {
                throw new StockInsuficienteException(
                    "Stock insuficiente en el depósito de origen para «{$producto->nombre}». Disponible: {$disponible}."
                );
            }
            $this->stockBloqueado($producto->id, $destinoId);

            $this->movimiento($producto->id, $origenId, 'transferencia_salida', $cantidad, 'transferencia', $destinoId);
            $this->movimiento($producto->id, $destinoId, 'transferencia_entrada', $cantidad, 'transferencia', $origenId);

            $this->setDeposito($producto->id, $origenId, $disponible - $cantidad);
            $this->setDeposito($producto->id, $destinoId, $this->stockBloqueado($producto->id, $destinoId) + $cantidad);
            // El total del producto no cambia en una transferencia.
        });
    }

    // ---- internos ----

    private function stockBloqueado(int $productoId, int $depositoId): int
    {
        $fila = DB::table('stock_deposito')
            ->where('producto_id', $productoId)
            ->where('deposito_id', $depositoId)
            ->lockForUpdate()
            ->first();

        if (! $fila) {
            DB::table('stock_deposito')->insert([
                'producto_id' => $productoId,
                'deposito_id' => $depositoId,
                'cantidad'    => 0,
            ]);

            return 0;
        }

        return (int) $fila->cantidad;
    }

    private function setDeposito(int $productoId, int $depositoId, int $cantidad): void
    {
        DB::table('stock_deposito')
            ->where('producto_id', $productoId)
            ->where('deposito_id', $depositoId)
            ->update(['cantidad' => $cantidad]);
    }

    private function ajustar(Producto $producto, int $depositoId, int $delta): void
    {
        DB::table('stock_deposito')
            ->where('producto_id', $producto->id)
            ->where('deposito_id', $depositoId)
            ->increment('cantidad', $delta);

        // Mantiene productos.stock como total denormalizado (lo usan reportes y alertas).
        Producto::whereKey($producto->id)->increment('stock', $delta);
        $producto->stock = (int) $producto->stock + $delta;
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
