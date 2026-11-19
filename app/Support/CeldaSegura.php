<?php

namespace App\Support;

/**
 * Una celda que empieza con = + - @ (o tab/retorno) la ejecuta Excel como fórmula:
 * un cliente llamado «=HYPERLINK(...)» se vuelve un ataque contra quien abre el
 * archivo. Se antepone un apóstrofo, que Excel muestra como texto.
 */
class CeldaSegura
{
    public static function limpiar(mixed $valor): mixed
    {
        if (! is_string($valor) || $valor === '') {
            return $valor;
        }

        return in_array($valor[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$valor : $valor;
    }
}
