<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Lo que comparten una venta y una compra: cabecera con fecha y estado, al
 * menos una línea, y un descuento opcional.
 *
 * Las reglas estaban duplicadas palabra por palabra en los dos controllers, que
 * es exactamente cómo se desincronizan: alcanzaba con que alguien permitiera
 * cantidad 0 en compras y no en ventas.
 */
abstract class ComprobanteRequest extends FormRequest
{
    /** El permiso ya lo verificó el middleware de la ruta. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas propias del tipo de comprobante (a quién se le vende o compra).
     *
     * @return array<string, mixed>
     */
    abstract protected function reglasDeCabecera(): array;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return array_merge($this->reglasDeCabecera(), [
            'deposito_id' => ['nullable', 'exists:depositos,id'],
            'fecha'       => ['required', 'date'],
            'estado'      => ['required', Rule::in(['pendiente', 'completada', 'cancelada'])],

            'detalles'               => ['required', 'array', 'min:1'],
            'detalles.*.producto_id' => ['required', 'exists:productos,id'],
            'detalles.*.cantidad'    => ['required', 'integer', 'min:1'],
            'detalles.*.precio'      => ['required', 'numeric', 'min:0'],

            'descuento'      => ['nullable', 'numeric', 'min:0'],
            'descuento_tipo' => ['nullable', Rule::in(['fijo', 'porcentaje'])],

            'metodos_pago'                  => ['nullable', 'array'],
            'metodos_pago.*.metodo_pago_id' => ['required_with:metodos_pago', 'exists:metodos_pago,id'],
            'metodos_pago.*.monto'          => ['required_with:metodos_pago', 'numeric', 'min:0'],
        ]);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'detalles.required'               => 'El comprobante tiene que tener al menos un producto.',
            'detalles.min'                    => 'El comprobante tiene que tener al menos un producto.',
            'detalles.*.producto_id.required' => 'Falta elegir el producto en una de las líneas.',
            'detalles.*.producto_id.exists'   => 'Una de las líneas apunta a un producto que no existe.',
            'detalles.*.cantidad.min'         => 'La cantidad de cada línea tiene que ser al menos 1.',
            'detalles.*.precio.min'           => 'El precio no puede ser negativo.',
        ];
    }

    /**
     * Un descuento en porcentaje mayor a 100 dejaría el total en negativo.
     */
    public function after(): array
    {
        return [
            function ($validator) {
                if ($this->input('descuento_tipo') === 'porcentaje' && (float) $this->input('descuento', 0) > 100) {
                    $validator->errors()->add('descuento', 'Un descuento en porcentaje no puede superar el 100%.');
                }
            },
        ];
    }
}
