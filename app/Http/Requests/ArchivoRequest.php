<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ArchivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // Lista blanca de tipos, no lista negra: lo que no está acá no sube.
            'archivo'          => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
            'relacionado_tipo' => ['nullable', 'string', 'max:100'],
            'relacionado_id'   => ['nullable', 'integer'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'archivo.mimes' => 'Solo se aceptan imágenes (jpg, png, webp) y PDF.',
            'archivo.max'   => 'El archivo no puede pasar de 10 MB.',
        ];
    }
}
