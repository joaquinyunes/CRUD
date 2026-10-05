<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductoRequest extends FormRequest
{
    /** El permiso ya lo verificó el middleware de la ruta. */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        // En una edición hay que ignorar el propio registro; y un producto
        // eliminado no bloquea el código, para poder reutilizarlo.
        $ignorar = $this->route('producto')?->id;
        $vivos = fn ($q) => $q->where('estado', '!=', 'eliminado');

        return [
            'codigo'           => ['nullable', 'string', 'max:100', Rule::unique('productos', 'codigo')->ignore($ignorar)->where($vivos)],
            'codigo_barra'     => ['nullable', 'string', 'max:100', Rule::unique('productos', 'codigo_barra')->ignore($ignorar)->where($vivos)],
            'nombre'           => ['required', 'string', 'max:255'],
            'descripcion'      => ['nullable', 'string'],
            'categoria_id'     => ['nullable', 'exists:categorias,id'],
            'marca'            => ['nullable', 'string', 'max:100'],
            'precio_compra'    => ['nullable', 'numeric', 'min:0'],
            'precio_venta'     => ['nullable', 'numeric', 'min:0'],
            'stock_minimo'     => ['nullable', 'integer', 'min:0'],
            'unidad_medida_id' => ['nullable', 'exists:unidades_medida,id'],
            'estado'           => ['nullable', Rule::in(['activo', 'inactivo'])],
            'imagen'           => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'codigo.unique'       => 'Ya hay otro producto con ese código.',
            'codigo_barra.unique' => 'Ya hay otro producto con ese código de barras.',
            'nombre.required'     => 'El producto necesita un nombre.',
        ];
    }
}
