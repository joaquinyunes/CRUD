<?php

namespace App\Support\Mongo;

use MongoDB\BSON\Decimal128;
use MongoDB\Laravel\Query\Builder;

/**
 * Query builder de los modelos Mongo con ids numéricos (decisión D1).
 *
 * Los ids viajan como texto (URL, formularios, validación) pero se guardan como
 * entero; Mongo no compara "5" con 5, así que se normalizan antes de consultar.
 */
class Consulta extends Builder
{
    protected function compileWheres(): array
    {
        foreach ($this->wheres ?? [] as $i => $where) {
            if (! is_string($where['column'] ?? null) || ! self::esClave($where['column'])) {
                continue;
            }

            foreach (['value', 'values'] as $campo) {
                if (isset($where[$campo])) {
                    $this->wheres[$i][$campo] = self::entero($where[$campo]);
                }
            }
        }

        return parent::compileWheres();
    }

    /**
     * sum(), avg(), min() y max() de un campo Decimal128 (el dinero, D6) vuelven
     * como Decimal128, que PHP no puede convertir a número: se devuelven como
     * texto exacto ("0.30"). La suma ya la hizo Mongo en decimal, sin float.
     */
    public function aggregate($function = null, $columns = ['*'])
    {
        $resultado = parent::aggregate($function, $columns);

        return $resultado instanceof Decimal128 ? (string) $resultado : $resultado;
    }

    public static function esClave(string $columna): bool
    {
        return $columna === 'id' || $columna === '_id' || str_ends_with($columna, '_id');
    }

    /** "12" pasa a 12; todo lo demás (ObjectId, fechas, otros textos) queda igual. */
    public static function entero(mixed $valor): mixed
    {
        if (is_array($valor)) {
            return array_map(self::entero(...), $valor);
        }

        return is_string($valor) && strlen($valor) <= 18 && ctype_digit($valor) ? (int) $valor : $valor;
    }
}
