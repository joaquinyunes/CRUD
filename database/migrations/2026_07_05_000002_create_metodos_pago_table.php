<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metodos_pago', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');          // Efectivo, Tarjeta Débito, etc.
            $table->string('codigo', 20);      // efectivo, debito, credito, transferencia, qr, cuenta_corriente
            $table->boolean('activo')->default(true);
            $table->boolean('permite_vuelto')->default(false);
            $table->integer('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metodos_pago');
    }
};
