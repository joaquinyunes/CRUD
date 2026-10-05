<?php

namespace App\Http\Requests;

class VentaRequest extends ComprobanteRequest
{
    /** @return array<string, mixed> */
    protected function reglasDeCabecera(): array
    {
        return [
            'cliente_id' => ['required', 'exists:clientes,id'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'cliente_id.required' => 'Hay que elegir un cliente.',
            'cliente_id.exists'   => 'El cliente elegido no existe.',
        ]);
    }
}
