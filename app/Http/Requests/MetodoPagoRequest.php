<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MetodoPagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100'],
            'codigo' => ['required', 'string', 'max:20',
                Rule::unique('metodos_pago', 'codigo')->ignore($this->route('metodo_pago')?->id)],
            'activo'         => ['nullable', 'boolean'],
            'permite_vuelto' => ['nullable', 'boolean'],
            'orden'          => ['nullable', 'integer', 'min:0'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['codigo.unique' => 'Ya hay un método de pago con ese código.'];
    }

    /** @return array<string, mixed> */
    public function datosParaGuardar(): array
    {
        return array_merge($this->validated(), [
            'activo'         => $this->boolean('activo'),
            'permite_vuelto' => $this->boolean('permite_vuelto'),
            'orden'          => (int) $this->input('orden', 0),
        ]);
    }
}
