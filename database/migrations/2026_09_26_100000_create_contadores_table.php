<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Una fila por serie de comprobante ("ventas:VTA", "compras:COM", ...).
 *
 * El número consecutivo se saca bloqueando esta fila, no buscando el máximo en
 * la tabla de comprobantes: un "SELECT ... FOR UPDATE" que no devuelve filas no
 * bloquea nada, así que con la tabla vacía dos operaciones simultáneas se
 * llevaban el mismo número.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contadores', function (Blueprint $table) {
            $table->id();
            $table->string('serie')->unique();
            $table->unsignedBigInteger('ultimo')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contadores');
    }
};
