<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Arrastrar un evento en el calendario: el navegador manda el nuevo inicio y fin. */
class MoverEventoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'start' => ['required', 'date'],
            'end'   => ['nullable', 'date', 'after_or_equal:start'],
        ];
    }
}
