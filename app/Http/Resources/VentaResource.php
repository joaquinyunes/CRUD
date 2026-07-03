<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VentaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'numero'      => $this->numero,
            'cliente_id'  => $this->cliente_id,
            'cliente'     => $this->whenLoaded('cliente'),
            'fecha'       => $this->fecha?->format('Y-m-d'),
            'total'       => $this->total,
            'estado'      => $this->estado,
            'user_id'     => $this->user_id,
            'user'        => $this->whenLoaded('user', fn () => ['id' => $this->user->id, 'name' => $this->user->name]),
            'detalles'    => VentaDetalleResource::collection($this->whenLoaded('detalles')),
            'created_at'  => $this->created_at?->toISOString(),
            'updated_at'  => $this->updated_at?->toISOString(),
        ];
    }
}
