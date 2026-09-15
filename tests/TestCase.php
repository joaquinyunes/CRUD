<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    /**
     * Categorias, unidades_medida, clientes y proveedores viven en Mongo
     * (fase 2 de la migracion): RefreshDatabase solo reinicia la conexion
     * SQL, asi que estas colecciones se limpian aca para que cada test
     * arranque sin datos de tests anteriores (evita choques con el indice
     * unico de "nombre" en categorias/unidades_medida).
     */
    protected function setUp(): void
    {
        parent::setUp();

        try {
            DB::connection('mongodb')->table('categorias')->truncate();
            DB::connection('mongodb')->table('unidades_medida')->truncate();
            DB::connection('mongodb')->table('clientes')->truncate();
            DB::connection('mongodb')->table('proveedores')->truncate();
        } catch (\Throwable) {
            // Sin Mongo disponible: los tests que dependan de Categoria/UnidadMedida
            // van a fallar mas abajo con un error de conexion explicito.
        }
    }
}
