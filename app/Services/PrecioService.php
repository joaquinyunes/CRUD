<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\ListaPrecio;
use App\Models\PrecioProducto;
use App\Models\Producto;
use App\Models\Promocion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Resuelve el precio de un producto para un cliente/lista y la mejor
 * promoción vigente. Lo usan el POS y las ventas de back-office.
 */
class PrecioService
{
    /**
     * Precio unitario base (sin promoción) para un producto.
     * Prioridad: precio explícito en la lista > ajuste porcentual de la lista > precio de venta.
     */
    public function precioBase(Producto $producto, ?int $listaPrecioId = null): float
    {
        $base = (float) $producto->precio_venta;

        if (! $listaPrecioId) {
            return round($base, 2);
        }

        $explicito = $producto->relationLoaded('preciosLista')
            ? optional($producto->preciosLista->firstWhere('lista_precio_id', $listaPrecioId))->precio
            : PrecioProducto::where('producto_id', $producto->id)
                ->where('lista_precio_id', $listaPrecioId)->value('precio');

        if ($explicito !== null) {
            return round((float) $explicito, 2);
        }

        $ajuste = (float) (ListaPrecio::whereKey($listaPrecioId)->value('ajuste_pct') ?? 0);

        return round($base * (1 + $ajuste / 100), 2);
    }

    /**
     * Mejor promoción vigente para un producto y cantidad. Devuelve
     * ['promocion' => Promocion, 'descuento' => float] o null.
     *
     * @param  Collection<int,Promocion>|null  $catalogo  promos ya cargadas (evita N+1 en el POS)
     */
    public function mejorPromocion(Producto $producto, float $cantidad, float $precioUnitario, ?Carbon $momento = null, $catalogo = null): ?array
    {
        $momento ??= now();

        $promos = $catalogo
            ? $catalogo->filter(fn (Promocion $p) => $this->coincide($p, $producto) && $p->aplicaADia($momento))
            : Promocion::vigentes($momento)->get()
                ->filter(fn (Promocion $p) => $this->coincide($p, $producto) && $p->aplicaADia($momento));

        $mejor = null;
        foreach ($promos as $promo) {
            $desc = $promo->descuentoPara($cantidad, $precioUnitario);
            if ($desc <= 0) {
                continue;
            }
            if (! $mejor || $desc > $mejor['descuento']
                || ($desc === $mejor['descuento'] && $promo->prioridad > $mejor['promocion']->prioridad)) {
                $mejor = ['promocion' => $promo, 'descuento' => $desc];
            }
        }

        return $mejor;
    }

    public function listaDe(?Cliente $cliente): ?int
    {
        return $cliente?->lista_precio_id;
    }

    private function coincide(Promocion $promo, Producto $producto): bool
    {
        return match ($promo->alcance) {
            'todos' => true,
            'categoria' => (int) $promo->categoria_id === (int) $producto->categoria_id,
            default => (int) $promo->producto_id === (int) $producto->id,
        };
    }
}
