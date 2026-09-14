<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

// Fase 2 (migracion a MongoDB): indices de las colecciones que reemplazan a
// las tablas categorias / unidades_medida. Correr despues de configurar
// MONGODB_URI. La copia de datos real la hace el comando
// `catalogo:migrar-a-mongo` (no esta migracion).
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mongodb')->create('categorias', function ($collection) {
            $collection->unique('nombre');
            $collection->index('estado');
        });

        Schema::connection('mongodb')->create('unidades_medida', function ($collection) {
            $collection->unique('nombre');
            $collection->index('estado');
        });
    }

    public function down(): void
    {
        Schema::connection('mongodb')->dropIfExists('categorias');
        Schema::connection('mongodb')->dropIfExists('unidades_medida');
    }
};
