<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferenciaStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'producto_id' => ['required', 'exists:productos,id'],
            'origen_id'   => ['required', 'exists:depositos,id'],
            'destino_id'  => ['required', 'exists:depositos,id', 'different:origen_id'],
            'cantidad'    => ['required', 'integer', 'min:1'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'destino_id.different' => 'El depósito de destino tiene que ser distinto al de origen.',
            'cantidad.min'         => 'Hay que transferir al menos una unidad.',
        ];
    }
}
