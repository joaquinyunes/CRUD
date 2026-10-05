<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Cambio de rol de un usuario desde el listado.
 *
 * Dejarlo vacío significa quitarle el rol; que eso esté permitido o no lo
 * decide el controller, porque depende de quién quede administrando el sistema.
 */
class AsignarRolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'role_id' => ['nullable', 'exists:roles,id'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['role_id.exists' => 'El rol elegido no existe.'];
    }
}
