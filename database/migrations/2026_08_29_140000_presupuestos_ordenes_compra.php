<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presupuestos', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->date('fecha');
            $table->unsignedSmallInteger('validez_dias')->default(15);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->string('descuento_tipo', 20)->nullable();
            $table->decimal('impuesto', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('estado', 20)->default('borrador'); // borrador|enviado|aceptado|rechazado|convertido|vencido
            $table->foreignId('venta_id')->nullable()->constrained('ventas')->nullOnDelete();
            $table->text('observaciones')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
            $table->index(['estado', 'fecha']);
        });

        Schema::create('presupuestos_detalle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('presupuesto_id')->constrained('presupuestos')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos');
            $table->integer('cantidad');
            $table->decimal('precio', 12, 2);
            $table->decimal('subtotal', 12, 2);
        });

        Schema::create('ordenes_compra', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->foreignId('proveedor_id')->constrained('proveedores');
            $table->date('fecha');
            $table->date('fecha_entrega_estimada')->nullable();
            $table->decimal('total', 12, 2)->default(0);
            $table->string('estado', 20)->default('borrador'); // borrador|enviada|parcial|recibida|cancelada
            $table->foreignId('compra_id')->nullable()->constrained('compras')->nullOnDelete();
            $table->text('observaciones')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
            $table->index(['estado', 'fecha']);
        });

        Schema::create('ordenes_compra_detalle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_compra_id')->constrained('ordenes_compra')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos');
            $table->integer('cantidad');
            $table->integer('cantidad_recibida')->default(0);
            $table->decimal('precio', 12, 2);
            $table->decimal('subtotal', 12, 2);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_compra_detalle');
        Schema::dropIfExists('ordenes_compra');
        Schema::dropIfExists('presupuestos_detalle');
        Schema::dropIfExists('presupuestos');
    }
};
