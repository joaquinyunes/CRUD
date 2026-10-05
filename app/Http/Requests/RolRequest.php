<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'permisos'   => ['nullable', 'array'],
            'permisos.*' => ['string', 'exists:permissions,clave'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['permisos.*.exists' => 'Alguno de los permisos enviados no existe.'];
    }
}
