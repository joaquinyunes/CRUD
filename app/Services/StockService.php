<?php

namespace App\Services;

use App\Models\MovimientoStock;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function registrarSalida(Producto $producto, int $cantidad, string $referenciaTipo, int $referenciaId): void
    {
        if ($cantidad <= 0) {
            throw new \InvalidArgumentException('La cantidad debe ser mayor a 0.');
        }

        DB::transaction(function () use ($producto, $cantidad, $referenciaTipo, $referenciaId) {
            MovimientoStock::create([
                'producto_id'     => $producto->id,
                'tipo'            => 'salida',
                'cantidad'        => $cantidad,
                'user_id'         => auth()->id(),
                'referencia_tipo' => $referenciaTipo,
                'referencia_id'   => $referenciaId,
            ]);

            $producto->update(['stock' => $producto->stock - $cantidad]);
        });
    }

    public function registrarEntrada(Producto $producto, int $cantidad, string $referenciaTipo, int $referenciaId): void
    {
        if ($cantidad <= 0) {
            throw new \InvalidArgumentException('La cantidad debe ser mayor a 0.');
        }

        DB::transaction(function () use ($producto, $cantidad, $referenciaTipo, $referenciaId) {
            MovimientoStock::create([
                'producto_id'     => $producto->id,
                'tipo'            => 'entrada',
                'cantidad'        => $cantidad,
                'user_id'         => auth()->id(),
                'referencia_tipo' => $referenciaTipo,
                'referencia_id'   => $referenciaId,
            ]);

            $producto->update(['stock' => $producto->stock + $cantidad]);
        });
    }

    public function registrarAjuste(Producto $producto, int $cantidadNueva, string $motivo): void
    {
        DB::transaction(function () use ($producto, $cantidadNueva, $motivo) {
            $diferencia = $cantidadNueva - $producto->stock;

            MovimientoStock::create([
                'producto_id'     => $producto->id,
                'tipo'            => 'ajuste',
                'cantidad'        => abs($diferencia),
                'user_id'         => auth()->id(),
                'referencia_tipo' => $motivo,
                'referencia_id'   => null,
            ]);

            $producto->update(['stock' => $cantidadNueva]);
        });
    }

    public function registrarDevolucion(Producto $producto, int $cantidad, string $referenciaTipo, int $referenciaId): void
    {
        if ($cantidad <= 0) {
            throw new \InvalidArgumentException('La cantidad debe ser mayor a 0.');
        }

        DB::transaction(function () use ($producto, $cantidad, $referenciaTipo, $referenciaId) {
            MovimientoStock::create([
                'producto_id'     => $producto->id,
                'tipo'            => 'devolucion',
                'cantidad'        => $cantidad,
                'user_id'         => auth()->id(),
                'referencia_tipo' => $referenciaTipo,
                'referencia_id'   => $referenciaId,
            ]);

            $producto->update(['stock' => $producto->stock + $cantidad]);
        });
    }
}
