<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EventoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'titulo'      => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'color'       => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'inicio'      => ['required', 'date'],
            'fin'         => ['nullable', 'date', 'after_or_equal:inicio'],
            'todo_el_dia' => ['nullable', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'titulo.required'    => 'El evento necesita un título.',
            'fin.after_or_equal' => 'El evento no puede terminar antes de empezar.',
        ];
    }

    /**
     * Un checkbox sin marcar no viaja en el formulario, así que su ausencia
     * significa que el evento no es de todo el día.
     *
     * @return array<string, mixed>
     */
    public function datosParaGuardar(): array
    {
        return array_merge($this->validated(), ['todo_el_dia' => $this->boolean('todo_el_dia')]);
    }
}
