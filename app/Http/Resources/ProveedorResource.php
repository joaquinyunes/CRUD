<?php

namespace App\Http\Resources;

use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Proveedor
 */
class ProveedorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'nombre'     => $this->nombre,
            'cuit'       => $this->cuit,
            'telefono'   => $this->telefono,
            'email'      => $this->email,
            'direccion'  => $this->direccion,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
