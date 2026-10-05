<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Un cobro a un cliente o un pago a un proveedor.
 *
 * Se puede imputar a un comprobante concreto o dejarlo a cuenta: en ese caso lo
 * reparte el servicio, por antigüedad.
 */
class ImputarPagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'venta_id'       => ['nullable', 'exists:ventas,id'],
            'compra_id'      => ['nullable', 'exists:compras,id'],
            'metodo_pago_id' => ['required', 'exists:metodos_pago,id'],
            'monto'          => ['required', 'numeric', 'min:0.01'],
            'referencia'     => ['nullable', 'string', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'metodo_pago_id.required' => 'Hay que indicar con qué medio se cobra.',
            'monto.min'               => 'El monto tiene que ser mayor a cero.',
        ];
    }
}
