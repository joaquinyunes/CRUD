<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas_pago', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained()->cascadeOnDelete();
            $table->foreignId('metodo_pago_id')->constrained('metodos_pago');
            $table->decimal('monto', 12, 2);
            $table->string('referencia')->nullable(); // Nro de comprobante, etc.
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas_pago');
    }
};
