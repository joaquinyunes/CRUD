<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La auditoría pasa a cubrir todos los módulos, así que la tabla crece rápido y
 * la pantalla la filtra por modelo, por usuario y por fecha.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auditoria', function (Blueprint $table) {
            $table->index(['modelo_afectado', 'modelo_id']);
            $table->index(['user_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('auditoria', function (Blueprint $table) {
            $table->dropIndex(['modelo_afectado', 'modelo_id']);
            $table->dropIndex(['user_id', 'created_at']);
            $table->dropIndex(['created_at']);
        });
    }
};
