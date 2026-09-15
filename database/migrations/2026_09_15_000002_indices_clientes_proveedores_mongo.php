<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

// Fase 2 (migracion a MongoDB): indices de las colecciones que reemplazan a
// las tablas clientes / proveedores. La copia de datos real la hace el
// comando `catalogo:migrar-a-mongo` (no esta migracion).
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mongodb')->create('clientes', function ($collection) {
            $collection->index('documento');
            $collection->index('estado');
        });

        Schema::connection('mongodb')->create('proveedores', function ($collection) {
            $collection->index('cuit');
        });
    }

    public function down(): void
    {
        Schema::connection('mongodb')->dropIfExists('clientes');
        Schema::connection('mongodb')->dropIfExists('proveedores');
    }
};
