<?php

namespace App\Support;

use Generator;

/**
 * Lee un CSV tal como lo guarda Excel, no como lo documenta el RFC.
 *
 * Las planillas que llegan a un sistema de gestión casi nunca son "a,b,c" en
 * UTF-8 limpio: Excel en español separa con punto y coma, guarda en Windows-1252
 * (o en UTF-8 con una marca BOM delante del primer encabezado), deja una línea en
 * blanco al final y a veces una fila con menos celdas que encabezados. Leerlo
 * con fgetcsv a pelo hacía que todas las filas fallaran con "el nombre es
 * obligatorio" (el encabezado se llamaba "\xEF\xBB\xBFnombre") o que una línea
 * en blanco terminara la importación con un error fatal.
 */
class LectorCsv
{
    /** @var list<string> */
    private array $encabezados = [];

    private string $delimitador = ',';

    private bool $esUtf8 = true;

    public function __construct(private string $ruta) {}

    /**
     * @return Generator<int, array<string,string|null>> número de fila (en el archivo) => celdas por encabezado
     */
    public function filas(): Generator
    {
        $contenido = (string) file_get_contents($this->ruta);

        // Marca de orden de bytes de UTF-8, que Excel agrega delante.
        if (str_starts_with($contenido, "\xEF\xBB\xBF")) {
            $contenido = substr($contenido, 3);
        }

        $this->esUtf8 = mb_check_encoding($contenido, 'UTF-8');

        $flujo = fopen('php://temp', 'r+');
        fwrite($flujo, $contenido);
        rewind($flujo);

        $primera = (string) fgets($flujo);
        $this->delimitador = $this->detectarDelimitador($primera);
        rewind($flujo);

        $fila = 0;
        while (($celdas = fgetcsv($flujo, 0, $this->delimitador)) !== false) {
            $fila++;

            if ($this->estaEnBlanco($celdas)) {
                continue;
            }

            $celdas = array_map(fn ($c) => $this->aUtf8((string) $c), $celdas);

            if ($this->encabezados === []) {
                $this->encabezados = array_map(fn ($c) => mb_strtolower(trim($c)), $celdas);

                continue;
            }

            // Una fila corta se completa con vacíos y una larga se recorta: el
            // desfase de una celda no tiene por qué tirar la fila entera.
            $celdas = array_slice(array_pad($celdas, count($this->encabezados), null), 0, count($this->encabezados));

            yield $fila => array_combine($this->encabezados, array_map(
                fn ($c) => $c === null ? null : trim($c),
                $celdas
            ));
        }

        fclose($flujo);
    }

    /** @return list<string> */
    public function encabezados(): array
    {
        return $this->encabezados;
    }

    /** Un número como lo escribe una persona ("1.234,50", "12,5", "1234.5") a float. */
    public static function numero(?string $texto): float
    {
        $texto = trim((string) $texto);
        $texto = preg_replace('/[^\d,.\-]/', '', $texto) ?? '';

        if ($texto === '' || $texto === '-') {
            return 0.0;
        }

        $coma = strrpos($texto, ',');
        $punto = strrpos($texto, '.');

        if ($coma !== false && $punto !== false) {
            // Gana como decimal el separador que aparece último.
            $texto = $coma > $punto
                ? str_replace(',', '.', str_replace('.', '', $texto))
                : str_replace(',', '', $texto);
        } elseif ($coma !== false) {
            $texto = str_replace(',', '.', $texto);
        }

        return (float) $texto;
    }

    private function detectarDelimitador(string $linea): string
    {
        $mejor = ',';
        $max = 0;

        foreach ([',', ';', "\t"] as $candidato) {
            $cuenta = substr_count($linea, $candidato);
            if ($cuenta > $max) {
                $max = $cuenta;
                $mejor = $candidato;
            }
        }

        return $mejor;
    }

    /** @param  list<string|null>  $celdas */
    private function estaEnBlanco(array $celdas): bool
    {
        return array_filter($celdas, fn ($c) => trim((string) $c) !== '') === [];
    }

    private function aUtf8(string $texto): string
    {
        return $this->esUtf8 ? $texto : mb_convert_encoding($texto, 'UTF-8', 'Windows-1252');
    }
}
