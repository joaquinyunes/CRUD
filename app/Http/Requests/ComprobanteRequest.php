<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Lo que comparten todos los documentos con líneas: ventas, compras,
 * presupuestos y órdenes de compra.
 *
 * Las reglas de las líneas y del descuento estaban repetidas palabra por
 * palabra en cuatro controllers, que es exactamente cómo se desincronizan:
 * alcanzaba con que alguien permitiera cantidad 0 en uno y no en los otros.
 *
 * Cada documento declara a quién se le emite, qué estados acepta y si lleva
 * pagos; el resto lo hereda.
 */
abstract class ComprobanteRequest extends FormRequest
{
    /** El permiso ya lo verificó el middleware de la ruta. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas propias del documento (a quién se le vende, compra o cotiza).
     *
     * @return array<string, mixed>
     */
    abstract protected function reglasDeCabecera(): array;

    /** @return list<string> */
    protected function estadosPermitidos(): array
    {
        return ['pendiente', 'completada', 'cancelada'];
    }

    protected function estadoRequerido(): bool
    {
        return true;
    }

    /** Un presupuesto o una orden de compra no se cobran: no aceptan pagos. */
    protected function aceptaPagos(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $reglas = array_merge($this->reglasDeCabecera(), [
            'deposito_id' => ['nullable', 'exists:depositos,id'],
            'fecha'       => ['required', 'date'],
            'estado'      => [
                $this->estadoRequerido() ? 'required' : 'nullable',
                Rule::in($this->estadosPermitidos()),
            ],

            'detalles'               => ['required', 'array', 'min:1'],
            'detalles.*.producto_id' => ['required', 'exists:productos,id'],
            'detalles.*.cantidad'    => ['required', 'integer', 'min:1'],
            'detalles.*.precio'      => ['required', 'numeric', 'min:0'],

            'descuento'      => ['nullable', 'numeric', 'min:0'],
            'descuento_tipo' => ['nullable', Rule::in(['fijo', 'monto', 'porcentaje'])],

            'observaciones' => ['nullable', 'string'],
        ]);

        if ($this->aceptaPagos()) {
            $reglas['metodos_pago'] = ['nullable', 'array'];
            $reglas['metodos_pago.*.metodo_pago_id'] = ['required_with:metodos_pago', 'exists:metodos_pago,id'];
            $reglas['metodos_pago.*.monto'] = ['required_with:metodos_pago', 'numeric', 'min:0'];
            $reglas['metodos_pago.*.referencia'] = ['nullable', 'string', 'max:255'];
        }

        return $reglas;
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
     *
     * @return array<int, callable>
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
