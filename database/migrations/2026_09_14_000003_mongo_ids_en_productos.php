<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Fase 2 (migracion a MongoDB): categorias y unidades_medida pasan a Mongo,
// asi que sus FK en SQL dejan de poder ser bigint con constraint -> pasan a
// string (van a guardar el ObjectId hex de 24 caracteres). Los datos existentes
// se migran con el comando `catalogo:migrar-a-mongo` (ver app/Console/Commands).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropForeign(['categoria_id']);
            $table->dropForeign(['unidad_medida_id']);
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->string('categoria_id', 24)->nullable()->change();
            $table->string('unidad_medida_id', 24)->nullable()->change();
        });

        Schema::table('promociones', function (Blueprint $table) {
            $table->dropForeign(['categoria_id']);
        });

        Schema::table('promociones', function (Blueprint $table) {
            $table->string('categoria_id', 24)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->unsignedBigInteger('categoria_id')->change();
            $table->unsignedBigInteger('unidad_medida_id')->nullable()->change();
        });
        Schema::table('productos', function (Blueprint $table) {
            $table->foreign('categoria_id')->references('id')->on('categorias');
            $table->foreign('unidad_medida_id')->references('id')->on('unidades_medida')->nullOnDelete();
        });

        Schema::table('promociones', function (Blueprint $table) {
            $table->unsignedBigInteger('categoria_id')->nullable()->change();
        });
        Schema::table('promociones', function (Blueprint $table) {
            $table->foreign('categoria_id')->references('id')->on('categorias')->cascadeOnDelete();
        });
    }
};
