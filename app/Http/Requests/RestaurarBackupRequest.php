<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Subida de un volcado para restaurar la base.
 *
 * Es la operación más destructiva del sistema, así que el archivo se acota por
 * tipo y por tamaño antes de que nadie lo mire.
 */
class RestaurarBackupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'backup_file' => ['required', 'file', 'extensions:gz', 'max:51240'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'backup_file.required'   => 'Hay que elegir el archivo de respaldo.',
            'backup_file.extensions' => 'El respaldo tiene que ser un archivo .json.gz creado por este sistema.',
            'backup_file.max'        => 'El respaldo no puede pasar de 50 MB.',
        ];
    }
}
