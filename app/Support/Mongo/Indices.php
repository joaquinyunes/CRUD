<?php

namespace App\Support\Mongo;

use Illuminate\Support\Facades\DB;

/**
 * Aplica database/mongo/indices.php. createIndex es idempotente: si el índice
 * ya existe igual, no hace nada.
 */
class Indices
{
    /** @return int cantidad de índices declarados */
    public static function crear(): int
    {
        $base = DB::connection('mongodb')->getDatabase();
        $total = 0;

        foreach (require database_path('mongo/indices.php') as $coleccion => $indices) {
            foreach ($indices as [$claves, $opciones]) {
                $base->selectCollection($coleccion)->createIndex($claves, $opciones);
                $total++;
            }
        }

        return $total;
    }
}
