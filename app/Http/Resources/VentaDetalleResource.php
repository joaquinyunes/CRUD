<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
