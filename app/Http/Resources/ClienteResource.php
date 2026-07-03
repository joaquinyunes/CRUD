<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClienteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'nombre'        => $this->nombre,
            'apellido'      => $this->apellido,
            'documento'     => $this->documento,
            'email'         => $this->email,
            'telefono'      => $this->telefono,
            'direccion'     => $this->direccion,
            'observaciones' => $this->observaciones,
            'estado'        => $this->estado,
            'created_at'    => $this->created_at?->toISOString(),
            'updated_at'    => $this->updated_at?->toISOString(),
        ];
    }
}
