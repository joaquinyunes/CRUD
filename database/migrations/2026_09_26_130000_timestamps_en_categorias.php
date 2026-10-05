<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La tabla de categorías no guardaba cuándo se creó ni cuándo se modificó cada
 * fila, pero la API las devolvía igual (siempre nulas). Toda tabla de negocio
 * tiene que poder decir desde cuándo existe cada registro.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            $table->dropTimestamps();
        });
    }
};
