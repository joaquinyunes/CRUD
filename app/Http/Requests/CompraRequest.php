<?php

namespace App\Http\Requests;

class CompraRequest extends ComprobanteRequest
{
    /** @return array<string, mixed> */
    protected function reglasDeCabecera(): array
    {
        return [
            'proveedor_id' => ['required', 'exists:proveedores,id'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'proveedor_id.required' => 'Hay que elegir un proveedor.',
            'proveedor_id.exists'   => 'El proveedor elegido no existe.',
        ]);
    }
}
