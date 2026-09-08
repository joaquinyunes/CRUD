<?php

namespace App\Support;

/**
 * Generador de códigos de barras Code 39 como SVG inline (sin dependencias).
 * Code 39 es legible por cualquier lector de retail y admite A-Z, 0-9 y - . $ / + % espacio.
 */
class Codebar
{
    /** Patrón de 9 elementos por carácter: n=angosto, w=ancho (bar,space,bar,...). */
    private const MAP = [
        '0' => 'nnnwwnwnn', '1' => 'wnnwnnnnw', '2' => 'nnwwnnnnw', '3' => 'wnwwnnnnn',
        '4' => 'nnnwwnnnw', '5' => 'wnnwwnnnn', '6' => 'nnwwwnnnn', '7' => 'nnnwnnwnw',
        '8' => 'wnnwnnwnn', '9' => 'nnwwnnwnn', 'A' => 'wnnnnwnnw', 'B' => 'nnwnnwnnw',
        'C' => 'wnwnnwnnn', 'D' => 'nnnnwwnnw', 'E' => 'wnnnwwnnn', 'F' => 'nnwnwwnnn',
        'G' => 'nnnnnwwnw', 'H' => 'wnnnnwwnn', 'I' => 'nnwnnwwnn', 'J' => 'nnnnwwwnn',
        'K' => 'wnnnnnnww', 'L' => 'nnwnnnnww', 'M' => 'wnwnnnnwn', 'N' => 'nnnnwnnww',
        'O' => 'wnnnwnnwn', 'P' => 'nnwnwnnwn', 'Q' => 'nnnnnnwww', 'R' => 'wnnnnnwwn',
        'S' => 'nnwnnnwwn', 'T' => 'nnnnwnwwn', 'U' => 'wwnnnnnnw', 'V' => 'nwwnnnnnw',
        'W' => 'wwwnnnnnn', 'X' => 'nwnnwnnnw', 'Y' => 'wwnnwnnnn', 'Z' => 'nwwnwnnnn',
        '-' => 'nwnnnnwnw', '.' => 'wwnnnnwnn', ' ' => 'nwwnnnwnn', '$' => 'nwnwnwnnn',
        '/' => 'nwnwnnnwn', '+' => 'nwnnnwnwn', '%' => 'nnnwnwnwn', '*' => 'nwnnwnwnn',
    ];

    public static function code39Svg(string $texto, int $alto = 48, int $anchoNarrow = 2): string
    {
        $texto = strtoupper(preg_replace('/[^0-9A-Z\-\. \$\/\+%]/', '', $texto)) ?: '0';
        $secuencia = '*'.$texto.'*';

        $wide = $anchoNarrow * 3;
        $x = 0;
        $rects = '';

        foreach (str_split($secuencia) as $i => $char) {
            $patron = self::MAP[$char] ?? self::MAP['*'];
            foreach (str_split($patron) as $j => $elem) {
                $w = $elem === 'w' ? $wide : $anchoNarrow;
                if ($j % 2 === 0) { // barra
                    $rects .= sprintf('<rect x="%d" y="0" width="%d" height="%d"/>', $x, $w, $alto);
                }
                $x += $w;
            }
            $x += $anchoNarrow; // gap entre caracteres
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" width="100%%" height="%d" preserveAspectRatio="none" fill="#000">%s</svg>',
            $x, $alto, $alto, $rects
        );
    }
}
