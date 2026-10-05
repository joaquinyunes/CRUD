<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'archivo' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            'tipo'    => ['required', Rule::in(['productos', 'clientes', 'proveedores'])],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'archivo.required' => 'Hay que elegir el archivo CSV a importar.',
            'archivo.mimes'    => 'El archivo tiene que ser un CSV.',
            'tipo.required'    => 'Indicá qué se está importando.',
        ];
    }
}
