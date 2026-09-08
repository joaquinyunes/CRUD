<?php

namespace App\Services;

use App\Models\Merma;
use App\Models\Producto;
use App\Models\Recuento;
use App\Support\NumeradorDocumentos;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Merma (baja de stock con motivo) y recuento de inventario (conteo físico
 * que ajusta el stock a lo contado). Se apoya en StockService y LoteService.
 */
class InventarioService
{
    public function __construct(
        private StockService $stock,
        private LoteService $lotes,
    ) {}

    public function registrarMerma(Producto $producto, int $depositoId, int $cantidad, string $motivo, ?string $observaciones = null): Merma
    {
        if ($cantidad <= 0) {
            throw new RuntimeException('La cantidad de la merma debe ser mayor a 0.');
        }

        return DB::transaction(function () use ($producto, $depositoId, $cantidad, $motivo, $observaciones) {
            $this->stock->registrarSalida($producto, $cantidad, "merma:{$motivo}", null, $depositoId);

            if ($this->lotes->controla($producto)) {
                $this->lotes->consumirFEFO($producto->id, $depositoId, $cantidad);
            }

            return Merma::create([
                'producto_id' => $producto->id,
                'deposito_id' => $depositoId,
                'cantidad' => $cantidad,
                'motivo' => $motivo,
                'costo' => round((float) $producto->precio_compra * $cantidad, 2),
                'observaciones' => $observaciones,
                'user_id' => auth()->id(),
            ]);
        });
    }

    /** @param  Collection<int,Producto>  $productos */
    public function crearRecuento(int $depositoId, $productos, ?string $observaciones = null): Recuento
    {
        return DB::transaction(function () use ($depositoId, $productos, $observaciones) {
            $recuento = Recuento::create([
                'numero' => NumeradorDocumentos::proximo('recuentos', 'REC'),
                'deposito_id' => $depositoId,
                'estado' => 'abierto',
                'observaciones' => $observaciones,
                'user_id' => auth()->id(),
            ]);

            $filas = $productos->map(fn (Producto $p) => [
                'producto_id' => $p->id,
                'stock_sistema' => $p->stockEn($depositoId),
                'contado' => null,
                'diferencia' => 0,
            ]);

            $recuento->detalles()->createMany($filas->all());

            return $recuento;
        });
    }

    /** @param  array<int,int|null>  $conteos  [producto_id => contado] */
    public function guardarConteo(Recuento $recuento, array $conteos): void
    {
        if ($recuento->estado !== 'abierto') {
            throw new RuntimeException('El recuento ya fue aplicado.');
        }

        $recuento->load('detalles');
        foreach ($recuento->detalles as $detalle) {
            if (! array_key_exists($detalle->producto_id, $conteos)) {
                continue;
            }
            $contado = $conteos[$detalle->producto_id];
            $detalle->contado = $contado === null || $contado === '' ? null : (int) $contado;
            $detalle->diferencia = $detalle->contado === null ? 0 : $detalle->contado - $detalle->stock_sistema;
            $detalle->save();
        }
    }

    public function aplicar(Recuento $recuento): void
    {
        if ($recuento->estado !== 'abierto') {
            throw new RuntimeException('El recuento ya fue aplicado o anulado.');
        }

        $recuento->load('detalles.producto');

        DB::transaction(function () use ($recuento) {
            foreach ($recuento->detalles as $detalle) {
                if ($detalle->contado === null || $detalle->contado === $detalle->stock_sistema) {
                    continue;
                }
                $this->stock->registrarAjuste(
                    $detalle->producto,
                    $detalle->contado,
                    "Recuento {$recuento->numero}",
                    $recuento->deposito_id,
                );
            }

            $recuento->update(['estado' => 'aplicado', 'aplicado_en' => now()]);
        });
    }
}
