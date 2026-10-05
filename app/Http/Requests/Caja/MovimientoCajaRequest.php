<?php

namespace App\Http\Requests\Caja;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MovimientoCajaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'tipo'     => ['required', Rule::in(['ingreso', 'egreso'])],
            'concepto' => ['required', 'string', 'max:255'],
            // Un movimiento de cero no es un movimiento.
            'monto' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'concepto.required' => 'Poné de qué se trata el movimiento.',
            'monto.min'         => 'El monto tiene que ser mayor a cero.',
        ];
    }
}
