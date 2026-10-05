<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfiguracionRequest extends FormRequest
{
    public const MONEDAS = ['ARS', 'USD', 'EUR', 'MXN', 'COP', 'PEN', 'BRL', 'CLP', 'UYU'];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * El formulario sugiere "VTA-" como ejemplo de prefijo, pero el guion lo
     * agrega el numerador: guardarlo con guion daba comprobantes "VTA--00001".
     */
    protected function prepareForValidation(): void
    {
        foreach (['ventas_prefijo_numero', 'compras_prefijo_numero'] as $campo) {
            if (is_string($this->input($campo))) {
                $this->merge([$campo => strtoupper(rtrim(trim($this->input($campo)), '-'))]);
            }
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'empresa_nombre'    => ['nullable', 'string', 'max:150'],
            'empresa_ruc'       => ['nullable', 'string', 'max:30'],
            'empresa_direccion' => ['nullable', 'string', 'max:255'],
            'empresa_telefono'  => ['nullable', 'string', 'max:50'],
            'empresa_email'     => ['nullable', 'email', 'max:150'],
            'empresa_logo'      => ['nullable', 'url:http,https', 'max:500'],

            'sistema_moneda'         => ['nullable', Rule::in(self::MONEDAS)],
            'sistema_simbolo_moneda' => ['nullable', 'string', 'max:5'],
            'sistema_iva'            => ['nullable', 'numeric', 'between:0,100'],

            'ventas_prefijo_numero'   => ['nullable', 'string', 'regex:/^[A-Z0-9]{1,10}$/'],
            'ventas_cantidad_digitos' => ['nullable', 'integer', 'between:3,10'],
            'ventas_limite_descuento' => ['nullable', 'numeric', 'between:0,100'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'empresa_logo.url'                => 'El logo tiene que ser una URL http o https.',
            'ventas_prefijo_numero.regex'     => 'El prefijo admite solo letras y números (hasta 10), sin guion ni espacios.',
            'ventas_cantidad_digitos.between' => 'La cantidad de dígitos va de 3 a 10.',
            'sistema_iva.between'             => 'El IVA va de 0 a 100.',
        ];
    }
}
