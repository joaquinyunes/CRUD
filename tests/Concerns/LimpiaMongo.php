<?php

namespace Tests\Concerns;

use App\Models\Deposito;
use App\Support\Mongo\Indices;
use Illuminate\Support\Facades\DB;

/**
 * Reemplaza a RefreshDatabase para los modelos que ya viven en MongoDB.
 *
 * Antes de cada test vacía todas las colecciones (sin borrarlas, así los
 * índices únicos siguen puestos y los tests los hacen valer) y deja el
 * depósito principal, que en SQL sembraba una migración. Los índices se
 * crean una vez por corrida. Necesita un mongod con replica set: las ventas
 * usan transacciones.
 */
trait LimpiaMongo
{
    private static bool $indicesMongoListos = false;

    protected function setUpLimpiaMongo(): void
    {
        $base = DB::connection('mongodb')->getDatabase();

        if (! self::$indicesMongoListos) {
            // Una base de pruebas vieja puede traer datos o índices de otra versión.
            $base->drop();
            Indices::crear();
            self::$indicesMongoListos = true;
        }

        foreach ($base->listCollectionNames() as $coleccion) {
            $base->selectCollection($coleccion)->deleteMany([]);
        }

        Deposito::create(['nombre' => 'Depósito principal', 'es_principal' => true, 'activo' => true]);
    }
}
