<?php

namespace App\Http\Requests;

use App\Models\Categoria;
use App\Models\Producto;
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

    /**
     * Los datos listos para guardar, con los valores por defecto resueltos.
     *
     * Un alta solo necesita el nombre: el código se genera, la categoría cae en
     * la primera activa y los importes arrancan en cero. Esto vivía dentro del
     * controller web, así que la misma alta por API fallaba o guardaba nulos.
     *
     * @return array<string, mixed>
     */
    public function datosParaGuardar(): array
    {
        $datos = $this->validated();

        if (blank($datos['codigo'] ?? null)) {
            $datos['codigo'] = self::codigoSiguiente();
        }

        $datos['categoria_id'] ??= Categoria::where('estado', true)->orderBy('id')->value('id');
        $datos['precio_compra'] ??= 0;
        $datos['precio_venta'] ??= 0;
        $datos['stock_minimo'] ??= 0;
        $datos['estado'] ??= 'activo';
        $datos['descripcion'] ??= '';

        return $datos;
    }

    /** El próximo código correlativo de la serie automática (PROD-0001). */
    public static function codigoSiguiente(): string
    {
        $ultimo = Producto::orderByDesc('id')->value('id') ?? 0;

        return 'PROD-'.str_pad((string) ($ultimo + 1), 4, '0', STR_PAD_LEFT);
    }
}
