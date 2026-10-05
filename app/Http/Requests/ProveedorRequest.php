<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProveedorRequest extends FormRequest
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
            'nombre'    => ['required', 'string', 'max:255'],
            'cuit'      => ['nullable', 'string', 'max:20'],
            'telefono'  => ['nullable', 'string', 'max:50'],
            'email'     => ['nullable', 'email', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'estado'    => ['nullable', Rule::in(['activo', 'archivado'])],
        ];
    }
}
