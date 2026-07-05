<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compras_pago', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compra_id')->constrained()->cascadeOnDelete();
            $table->foreignId('metodo_pago_id')->constrained('metodos_pago');
            $table->decimal('monto', 12, 2);
            $table->string('referencia')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compras_pago');
    }
};
