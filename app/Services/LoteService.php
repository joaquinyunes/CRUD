<?php

namespace App\Services;

use App\Models\Producto;
use App\Models\ProductoLote;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Ledger de lotes con vencimiento, en paralelo al stock por depósito.
 * Sólo aplica a productos con `controla_vencimiento`. El consumo es FEFO
 * (First Expired, First Out) y best-effort: nunca bloquea una venta.
 */
class LoteService
{
    public function ingresar(int $productoId, int $depositoId, float $cantidad, ?string $lote, ?string $vencimiento): ProductoLote
    {
        $fila = ProductoLote::firstOrNew([
            'producto_id' => $productoId,
            'deposito_id' => $depositoId,
            'lote' => $lote,
            'vencimiento' => $vencimiento,
        ]);

        $fila->cantidad = round((float) $fila->cantidad + $cantidad, 3);
        $fila->save();

        return $fila;
    }

    /** Consume `$cantidad` del stock de lotes en orden FEFO. Devuelve lo efectivamente consumido. */
    public function consumirFEFO(int $productoId, int $depositoId, float $cantidad): float
    {
        if ($cantidad <= 0) {
            return 0;
        }

        return DB::transaction(function () use ($productoId, $depositoId, $cantidad) {
            $restante = $cantidad;

            $lotes = ProductoLote::where('producto_id', $productoId)
                ->where('deposito_id', $depositoId)
                ->conStock()->fefo()->lockForUpdate()->get();

            foreach ($lotes as $lote) {
                if ($restante <= 0) {
                    break;
                }
                $toma = min((float) $lote->cantidad, $restante);
                $lote->cantidad = round((float) $lote->cantidad - $toma, 3);
                $lote->save();
                $restante = round($restante - $toma, 3);
            }

            return round($cantidad - max($restante, 0), 3);
        });
    }

    public function devolver(int $productoId, int $depositoId, float $cantidad): void
    {
        if ($cantidad <= 0) {
            return;
        }
        // Reingresa al lote más reciente con vencimiento; si no hay, crea uno sin datos.
        $lote = ProductoLote::where('producto_id', $productoId)
            ->where('deposito_id', $depositoId)
            ->orderByDesc('id')->first();

        if ($lote) {
            $lote->increment('cantidad', $cantidad);
        } else {
            $this->ingresar($productoId, $depositoId, $cantidad, null, null);
        }
    }

    /** Productos con lotes por vencer dentro de su ventana de alerta propia. */
    public function porVencer(): Collection
    {
        return ProductoLote::with('producto', 'deposito')
            ->conStock()
            ->whereNotNull('vencimiento')
            ->whereHas('producto', fn ($q) => $q->where('controla_vencimiento', true))
            ->get()
            ->filter(function (ProductoLote $l) {
                $dias = $l->producto->dias_alerta_vencimiento ?: 30;

                return $l->vencimiento->lte(now()->addDays($dias));
            })
            ->sortBy('vencimiento')
            ->values();
    }

    public function controla(Producto $producto): bool
    {
        return (bool) $producto->controla_vencimiento;
    }
}
