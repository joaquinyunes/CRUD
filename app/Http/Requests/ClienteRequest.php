<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClienteRequest extends FormRequest
{
    /** El permiso ya lo verificó el middleware de la ruta. */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'nombre'         => ['required', 'string', 'max:255'],
            'apellido'       => ['required', 'string', 'max:255'],
            'documento'      => ['nullable', 'string', 'max:50'],
            'email'          => ['nullable', 'email', 'max:255'],
            'telefono'       => ['nullable', 'string', 'max:50'],
            'direccion'      => ['nullable', 'string', 'max:255'],
            'observaciones'  => ['nullable', 'string'],
            'estado'         => ['required', Rule::in(['activo', 'archivado', 'eliminado'])],
            'limite_credito' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
