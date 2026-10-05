<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TareaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        // Al crear no se puede fechar en el pasado. Al editar sí, porque una
        // tarea que ya venció sigue teniendo que poder corregirse.
        $esAlta = $this->route('tarea') === null;

        return [
            'titulo'       => ['required', 'string', 'max:255'],
            'descripcion'  => ['nullable', 'string'],
            'prioridad'    => ['required', Rule::in(['baja', 'media', 'alta'])],
            'estado'       => ['required', Rule::in(['pendiente', 'en_progreso', 'completada'])],
            'fecha_limite' => $esAlta
                ? ['nullable', 'date', 'after_or_equal:today']
                : ['nullable', 'date'],
            'asignada_a' => ['nullable', 'exists:users,id'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'titulo.required'             => 'La tarea necesita un título.',
            'fecha_limite.after_or_equal' => 'La fecha límite no puede ser anterior a hoy.',
        ];
    }
}
