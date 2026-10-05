<?php

namespace App\Http\Requests\Caja;

use Illuminate\Foundation\Http\FormRequest;

class CerrarCajaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // Lo que el cajero contó. La diferencia con lo esperado es el arqueo.
            'monto_final_declarado' => ['required', 'numeric', 'min:0'],
            'observaciones'         => ['nullable', 'string', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['monto_final_declarado.required' => 'Hay que declarar el efectivo contado para poder cerrar.'];
    }
}
