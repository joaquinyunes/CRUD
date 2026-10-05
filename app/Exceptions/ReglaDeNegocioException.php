<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Una regla de negocio que no se cumple: el cliente está archivado, la venta
 * supera su límite de crédito.
 *
 * No es un error de validación de formato —eso lo resuelven los FormRequests—
 * sino una condición que solo se puede evaluar con el estado del sistema a la
 * vista. Lleva el campo al que corresponde el mensaje para que la interfaz web
 * lo muestre donde va, y la API lo devuelva con la misma forma.
 */
class ReglaDeNegocioException extends RuntimeException
{
    public function __construct(string $mensaje, public readonly string $campo = 'general')
    {
        parent::__construct($mensaje);
    }
}
