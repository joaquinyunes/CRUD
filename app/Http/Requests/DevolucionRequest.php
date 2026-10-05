<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Una devolución parcial: se listan todas las líneas del comprobante original y
 * se indica cuánto vuelve de cada una, así que la cantidad puede ser cero.
 */
class DevolucionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'motivo'                 => ['nullable', 'string', 'max:255'],
            'reembolso_efectivo'     => ['nullable', 'boolean'],
            'detalles'               => ['required', 'array', 'min:1'],
            'detalles.*.producto_id' => ['required', 'exists:productos,id'],
            'detalles.*.cantidad'    => ['required', 'integer', 'min:0'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'detalles.required'       => 'No hay nada para devolver.',
            'detalles.*.cantidad.min' => 'La cantidad a devolver no puede ser negativa.',
        ];
    }
}
