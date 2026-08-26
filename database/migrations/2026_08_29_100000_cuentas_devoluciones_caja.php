<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->decimal('limite_credito', 12, 2)->default(0)->after('estado');
        });

        Schema::create('caja_sesiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->decimal('monto_inicial', 12, 2)->default(0);
            $table->decimal('monto_final_declarado', 12, 2)->nullable();
            $table->decimal('monto_final_sistema', 12, 2)->nullable();
            $table->decimal('diferencia', 12, 2)->nullable();
            $table->string('estado', 20)->default('abierta'); // abierta | cerrada
            $table->text('observaciones')->nullable();
            $table->timestamp('abierta_en')->useCurrent();
            $table->timestamp('cerrada_en')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'estado']);
        });

        Schema::create('caja_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caja_sesion_id')->constrained('caja_sesiones')->cascadeOnDelete();
            $table->string('tipo', 20); // ingreso | egreso
            $table->string('concepto');
            $table->decimal('monto', 12, 2);
            $table->string('referencia_tipo')->nullable(); // venta | compra | devolucion | manual
            $table->unsignedBigInteger('referencia_id')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
            $table->index(['caja_sesion_id', 'tipo']);
        });

        Schema::create('devoluciones', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->string('tipo', 10); // venta | compra
            $table->foreignId('venta_id')->nullable()->constrained('ventas')->nullOnDelete();
            $table->foreignId('compra_id')->nullable()->constrained('compras')->nullOnDelete();
            $table->date('fecha');
            $table->string('motivo')->nullable();
            $table->decimal('total', 12, 2)->default(0);
            $table->string('estado', 20)->default('registrada'); // registrada | anulada
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
            $table->index(['tipo', 'fecha']);
        });

        Schema::create('devoluciones_detalle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('devolucion_id')->constrained('devoluciones')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos');
            $table->integer('cantidad');
            $table->decimal('precio', 12, 2);
            $table->decimal('subtotal', 12, 2);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devoluciones_detalle');
        Schema::dropIfExists('devoluciones');
        Schema::dropIfExists('caja_movimientos');
        Schema::dropIfExists('caja_sesiones');
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('limite_credito');
        });
    }
};
