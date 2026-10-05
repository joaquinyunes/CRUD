<?php

namespace App\Http\Resources;

use App\Models\VentaDetalle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin VentaDetalle
 */
class VentaDetalleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'venta_id'    => $this->venta_id,
            'producto_id' => $this->producto_id,
            'producto'    => $this->whenLoaded('producto'),
            'cantidad'    => $this->cantidad,
            'precio'      => $this->precio,
            'subtotal'    => $this->subtotal,
        ];
    }
}
