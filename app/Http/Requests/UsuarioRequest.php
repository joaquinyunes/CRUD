<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UsuarioRequest extends FormRequest
{
    /** El permiso ya lo verificó el middleware de la ruta. */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $usuario = $this->route('usuario');
        $esAlta = $usuario === null;

        return [
            'name'    => ['required', 'string', 'max:255'],
            'email'   => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario?->id)],
            'role_id' => ['nullable', 'exists:roles,id'],
            // En una edición, dejar la contraseña vacía significa "no cambiarla".
            'password' => [$esAlta ? 'required' : 'nullable', 'confirmed', Password::min(8)],
            'activo'   => ['nullable', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'email.unique'       => 'Ya hay un usuario con ese correo.',
            'password.min'       => 'La contraseña tiene que tener al menos 8 caracteres.',
            'password.confirmed' => 'Las dos contraseñas no coinciden.',
        ];
    }
}
