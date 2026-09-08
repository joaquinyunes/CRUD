<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listas_precio', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->decimal('ajuste_pct', 6, 2)->default(0); // +/- % sobre el precio de venta base
            $table->boolean('activa')->default(true);
            $table->integer('orden')->default(0);
            $table->timestamps();
        });

        // Precio explícito por producto para una lista (pisa el ajuste porcentual).
        Schema::create('precios_producto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('lista_precio_id')->constrained('listas_precio')->cascadeOnDelete();
            $table->decimal('precio', 12, 2);
            $table->unique(['producto_id', 'lista_precio_id']);
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->foreignId('lista_precio_id')->nullable()->after('limite_credito')
                ->constrained('listas_precio')->nullOnDelete();
        });

        Schema::create('promociones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('tipo', 20);            // porcentaje | monto | precio_fijo | nxm
            $table->decimal('valor', 12, 2)->nullable();
            $table->unsignedInteger('n')->nullable(); // NxM: llevás N
            $table->unsignedInteger('m')->nullable(); // NxM: pagás M
            $table->string('alcance', 20)->default('producto'); // producto | categoria | todos
            $table->foreignId('producto_id')->nullable()->constrained('productos')->cascadeOnDelete();
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->cascadeOnDelete();
            $table->date('desde')->nullable();
            $table->date('hasta')->nullable();
            $table->time('hora_desde')->nullable();
            $table->time('hora_hasta')->nullable();
            $table->json('dias')->nullable();      // [1,2,3] = lun-mié (ISO-8601, 1=lunes)
            $table->boolean('activa')->default(true);
            $table->integer('prioridad')->default(0);
            $table->timestamps();
        });

        Schema::table('ventas_detalle', function (Blueprint $table) {
            $table->decimal('descuento_promo', 12, 2)->default(0)->after('subtotal');
            $table->foreignId('promocion_id')->nullable()->after('descuento_promo')
                ->constrained('promociones')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ventas_detalle', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promocion_id');
            $table->dropColumn('descuento_promo');
        });
        Schema::table('clientes', fn (Blueprint $t) => $t->dropConstrainedForeignId('lista_precio_id'));
        Schema::dropIfExists('promociones');
        Schema::dropIfExists('precios_producto');
        Schema::dropIfExists('listas_precio');
    }
};
