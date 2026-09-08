<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mermas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos');
            $table->foreignId('deposito_id')->constrained('depositos');
            $table->integer('cantidad');
            $table->string('motivo', 30);           // rotura | vencimiento | robo | consumo_interno | ajuste
            $table->decimal('costo', 12, 2)->default(0);
            $table->string('observaciones')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['producto_id', 'created_at']);
        });

        Schema::create('recuentos', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->foreignId('deposito_id')->constrained('depositos');
            $table->string('estado', 20)->default('abierto'); // abierto | aplicado | anulado
            $table->string('observaciones')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('aplicado_en')->nullable();
            $table->timestamps();
        });

        Schema::create('recuento_detalle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recuento_id')->constrained('recuentos')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos');
            $table->integer('stock_sistema');
            $table->integer('contado')->nullable();
            $table->integer('diferencia')->default(0);
            $table->unique(['recuento_id', 'producto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recuento_detalle');
        Schema::dropIfExists('recuentos');
        Schema::dropIfExists('mermas');
    }
};
