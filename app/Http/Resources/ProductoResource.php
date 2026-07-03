<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'codigo'         => $this->codigo,
            'nombre'         => $this->nombre,
            'descripcion'    => $this->descripcion,
            'categoria_id'   => $this->categoria_id,
            'categoria'      => $this->whenLoaded('categoria'),
            'marca'          => $this->marca,
            'precio_compra'  => $this->precio_compra,
            'precio_venta'   => $this->precio_venta,
            'stock'          => $this->stock,
            'stock_minimo'   => $this->stock_minimo,
            'imagen'         => $this->imagen,
            'estado'         => $this->estado,
            'created_at'     => $this->created_at?->toISOString(),
            'updated_at'     => $this->updated_at?->toISOString(),
        ];
    }
}
