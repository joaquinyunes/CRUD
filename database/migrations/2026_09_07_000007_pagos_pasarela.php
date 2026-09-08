<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos_pasarela', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->nullable()->constrained('ventas')->nullOnDelete();
            $table->string('pasarela', 20)->default('mercadopago');
            $table->string('external_id')->nullable()->index();
            $table->string('external_ref')->nullable()->index(); // referencia propia que mandamos a MP
            $table->decimal('monto', 12, 2);
            $table->string('estado', 20)->default('pendiente'); // pendiente | aprobado | rechazado | cancelado | expirado
            $table->text('qr_data')->nullable();
            $table->decimal('neto_acreditado', 12, 2)->nullable();
            $table->decimal('comision', 12, 2)->nullable();
            $table->date('fecha_acreditacion')->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_pasarela');
    }
};
