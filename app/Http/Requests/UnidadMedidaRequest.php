<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UnidadMedidaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'nombre'      => ['required', 'string', 'max:100'],
            'abreviacion' => ['required', 'string', 'max:10'],
            'estado'      => ['nullable', 'boolean'],
        ];
    }

    /** @return array<string, mixed> */
    public function datosParaGuardar(): array
    {
        return array_merge($this->validated(), ['estado' => $this->boolean('estado')]);
    }
}
