<?php

namespace App\Support;

use App\Models\ModeloMongo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Ordenamiento de listados por querystring (?orden=columna&dir=asc|desc).
 *
 * El mapa de columnas define que se puede ordenar y como: el valor puede ser
 * el nombre de una columna SQL o un closure para los casos con join.
 */
class Orden
{
    /**
     * @param  array<string, string|\Closure|\Illuminate\Database\Eloquent\Builder<*>>  $columnas  alias => columna SQL, subconsulta o closure(query, dir)
     * @param  string  $porDefecto  alias usado cuando no vino ?orden o vino uno invalido
     * @param  string  $dirPorDefecto  direccion inicial de ese alias
     */
    public static function aplicar(
        Builder $query,
        array $columnas,
        string $porDefecto,
        string $dirPorDefecto = 'asc',
        ?Request $request = null
    ): Builder {
        $request = $request ?: request();

        $alias = (string) $request->query('orden', '');
        $dir = strtolower((string) $request->query('dir', '')) === 'desc' ? 'desc' : 'asc';

        // Alias desconocido (o ausente): cae al orden por defecto con su propia direccion.
        if (! array_key_exists($alias, $columnas)) {
            $alias = $porDefecto;
            $dir = strtolower($dirPorDefecto) === 'desc' ? 'desc' : 'asc';
        }

        $destino = $columnas[$alias] ?? null;

        if ($destino instanceof \Closure) {
            // Casos con join o varios criterios.
            $destino($query, $dir);
        } elseif ($destino instanceof Builder && $destino->getModel() instanceof ModeloMongo) {
            // shortcut: no se puede ordenar por el campo de un documento de otra colección
            // (en SQL era una subconsulta correlacionada). Se ordena por la clave foránea; el
            // orden por nombre de la relación se resuelve con $lookup en la fase 5.
            $query->orderBy(self::claveForanea($destino), $dir);
        } elseif ($destino !== null) {
            // string, subconsulta (Builder) o expresion cruda.
            $query->orderBy($destino, $dir);
        }

        return $query;
    }

    /** La columna de la tabla principal en `whereColumn('otra.id', 'principal.clave_foranea')`. */
    private static function claveForanea(Builder $subconsulta): string
    {
        foreach ($subconsulta->getQuery()->wheres as $where) {
            if (($where['type'] ?? null) === 'Column') {
                return substr((string) strrchr('.'.$where['second'], '.'), 1);
            }
        }

        return '_id';
    }
}
