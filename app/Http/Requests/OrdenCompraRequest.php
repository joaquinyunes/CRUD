<?php

namespace App\Http\Requests;

class OrdenCompraRequest extends ComprobanteRequest
{
    /** @return array<string, mixed> */
    protected function reglasDeCabecera(): array
    {
        return [
            'proveedor_id'           => ['required', 'exists:proveedores,id'],
            'fecha_entrega_estimada' => ['nullable', 'date', 'after_or_equal:fecha'],
        ];
    }

    /** @return list<string> */
    protected function estadosPermitidos(): array
    {
        return ['borrador', 'enviada'];
    }

    protected function estadoRequerido(): bool
    {
        return false;
    }

    /** Una orden se factura al recibirla; los pagos van en la compra. */
    protected function aceptaPagos(): bool
    {
        return false;
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'proveedor_id.required'                 => 'Hay que elegir un proveedor.',
            'fecha_entrega_estimada.after_or_equal' => 'La entrega estimada no puede ser anterior a la fecha de la orden.',
        ]);
    }
}
