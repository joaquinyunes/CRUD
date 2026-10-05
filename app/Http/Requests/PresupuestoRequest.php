<?php

namespace App\Http\Requests;

class PresupuestoRequest extends ComprobanteRequest
{
    /** @return array<string, mixed> */
    protected function reglasDeCabecera(): array
    {
        return [
            'cliente_id'   => ['required', 'exists:clientes,id'],
            'validez_dias' => ['nullable', 'integer', 'min:1', 'max:365'],
        ];
    }

    /** @return list<string> */
    protected function estadosPermitidos(): array
    {
        return ['borrador', 'enviado', 'aceptado', 'rechazado'];
    }

    protected function estadoRequerido(): bool
    {
        return false;
    }

    /** Un presupuesto no se cobra: se convierte en venta y ahí se cobra. */
    protected function aceptaPagos(): bool
    {
        return false;
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'cliente_id.required' => 'Hay que elegir un cliente.',
            'validez_dias.max'    => 'La validez no puede pasar de un año.',
        ]);
    }
}
