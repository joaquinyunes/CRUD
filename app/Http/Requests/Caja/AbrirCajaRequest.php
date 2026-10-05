<?php

namespace App\Http\Requests\Caja;

use Illuminate\Foundation\Http\FormRequest;

class AbrirCajaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'monto_inicial' => ['required', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['monto_inicial.required' => 'Hay que declarar con cuánto efectivo abre la caja.'];
    }
}
