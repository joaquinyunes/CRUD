<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Fase 2 (migracion a MongoDB): los modelos migrados a Mongo tienen _id tipo
// ObjectId (string de 24 hex), no bigint. Auditable::bootAuditable() guarda
// $model->getKey() en modelo_id, asi que la columna tiene que aceptar ambos
// (bigint de modelos SQL todavia no migrados, y ObjectId de los migrados).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auditoria', function (Blueprint $table) {
            $table->string('modelo_id', 64)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('auditoria', function (Blueprint $table) {
            $table->unsignedBigInteger('modelo_id')->nullable()->change();
        });
    }
};
