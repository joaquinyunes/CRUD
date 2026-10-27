<?php

namespace Tests\Concerns;

use App\Models\Deposito;
use Illuminate\Support\Facades\DB;

/**
 * Reemplaza a RefreshDatabase para los modelos que ya viven en MongoDB:
 * vacía la base de pruebas antes de cada test y deja el depósito principal
 * (que en SQL sembraba una migración). Necesita un mongod con replica set:
 * las ventas usan transacciones.
 */
trait LimpiaMongo
{
    protected function setUpLimpiaMongo(): void
    {
        $base = DB::connection('mongodb')->getDatabase();

        foreach ($base->listCollectionNames() as $coleccion) {
            $base->dropCollection($coleccion);
        }

        Deposito::create(['nombre' => 'Depósito principal', 'es_principal' => true, 'activo' => true]);
    }
}
