<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoriaRequest extends FormRequest
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
            'nombre' => ['required', 'string', 'max:255',
                Rule::unique('categorias', 'nombre')->ignore($this->route('categoria')?->id)],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'estado'      => ['nullable', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'nombre.required' => 'La categoría necesita un nombre.',
            'nombre.unique'   => 'Ya existe una categoría con ese nombre.',
        ];
    }

    /**
     * Un checkbox que no se marca no viaja en el formulario, así que "estado"
     * ausente significa desactivada, no "dejar como estaba".
     *
     * @return array<string, mixed>
     */
    public function datosParaGuardar(): array
    {
        return array_merge($this->validated(), ['estado' => $this->boolean('estado')]);
    }
}
