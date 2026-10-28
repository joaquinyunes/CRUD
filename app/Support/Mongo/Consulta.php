<?php

namespace App\Support\Mongo;

use Illuminate\Support\Carbon;
use MongoDB\BSON\Decimal128;
use MongoDB\BSON\UTCDateTime;
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
            if (! is_string($where['column'] ?? null)) {
                continue;
            }

            // whereDate/whereMonth/whereYear ya los entiende el paquete: no se tocan.
            $propia = in_array(strtolower((string) ($where['type'] ?? '')), ['date', 'day', 'month', 'year', 'time'], true);

            $normalizar = match (true) {
                $propia                         => null,
                self::esClave($where['column']) => self::entero(...),
                self::esFecha($where['column']) => self::fecha(...),
                default                         => null,
            };

            foreach (['value', 'values'] as $campo) {
                if ($normalizar && isset($where[$campo])) {
                    $this->wheres[$i][$campo] = $normalizar($where[$campo]);
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

    /**
     * Compara dos campos del mismo documento (`stock <= stock_minimo`) con $expr.
     *
     * Con nombres de otra tabla (`users.id`, `ventas.user_id`) no hay comparación posible:
     * el código de los listados arma `Modelo::select('nombre')->whereColumn(...)` solo para
     * decirle a Orden por qué clave ordenar, así que se registra sin ejecutarla.
     */
    public function whereColumn($first, $operator = null, $second = null, $boolean = 'and')
    {
        if ($second === null) {
            [$second, $operator] = [$operator, '='];
        }

        if (str_contains((string) $first, '.') || str_contains((string) $second, '.')) {
            $this->wheres[] = ['type' => 'Column', 'first' => $first, 'operator' => $operator, 'second' => $second, 'boolean' => $boolean];

            return $this;
        }

        $mongo = ['=' => '$eq', '!=' => '$ne', '<>' => '$ne', '<' => '$lt', '<=' => '$lte', '>' => '$gt', '>=' => '$gte'][$operator]
            ?? throw new \InvalidArgumentException("Operador no soportado en whereColumn: {$operator}");

        return $this->whereRaw(['$expr' => [$mongo => ['$'.$first, '$'.$second]]], [], $boolean);
    }

    /** Eloquent la usa al cargar relaciones con ids enteros; en Mongo es un whereIn común. */
    public function whereIntegerInRaw($column, $values, $boolean = 'and', $not = false)
    {
        return $this->whereIn($column, $values, $boolean, $not);
    }

    /** Columnas que guardan fechas: `fecha`, `created_at`, `cerrada_en`... */
    public static function esFecha(string $columna): bool
    {
        return $columna === 'fecha' || str_ends_with($columna, '_at') || str_ends_with($columna, '_en');
    }

    /**
     * "2026-10-27" o "2026-10-27 23:59:59" pasa a fecha de Mongo; los filtros de los
     * listados mandan texto y Mongo no compara un texto con una fecha.
     */
    public static function fecha(mixed $valor): mixed
    {
        if (is_array($valor)) {
            return array_map(self::fecha(...), $valor);
        }

        if (is_string($valor) && preg_match('/^\d{4}-\d{2}-\d{2}([ T]\d{2}:\d{2}(:\d{2})?)?$/', $valor)) {
            return new UTCDateTime(Carbon::parse($valor));
        }

        return $valor;
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
