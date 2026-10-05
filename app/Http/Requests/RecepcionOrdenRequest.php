<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Recepción de mercadería de una orden de compra.
 *
 * Llega como un mapa de id de línea => cantidad recibida, y se puede recibir
 * parcialmente: las líneas en cero simplemente no se tocan.
 */
class RecepcionOrdenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'recibido'   => ['required', 'array'],
            'recibido.*' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['recibido.*.min' => 'No se puede recibir una cantidad negativa.'];
    }
}
