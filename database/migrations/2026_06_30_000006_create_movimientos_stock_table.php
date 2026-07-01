<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos');
            $table->enum('tipo', ['entrada', 'salida', 'ajuste', 'devolucion']);
            $table->integer('cantidad');
            $table->foreignId('user_id')->constrained('users');
            $table->string('referencia_tipo')->nullable();
            $table->bigInteger('referencia_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_stock');
    }
};
