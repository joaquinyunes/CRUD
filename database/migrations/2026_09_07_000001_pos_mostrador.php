<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Códigos de barra múltiples por producto (EAN, código interno, pack, etc.).
        Schema::create('producto_codigos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->string('codigo')->unique();
            $table->string('descripcion')->nullable();      // "Pack x6", "Unidad", ...
            $table->decimal('factor', 12, 3)->default(1);    // unidades que representa este código
            $table->boolean('principal')->default(false);
            $table->timestamps();
            $table->index('producto_id');
        });

        // Migra el código de barra actual de cada producto a la nueva tabla.
        foreach (DB::table('productos')->whereNotNull('codigo_barra')->where('codigo_barra', '!=', '')->get() as $p) {
            DB::table('producto_codigos')->insertOrIgnore([
                'producto_id' => $p->id,
                'codigo' => $p->codigo_barra,
                'descripcion' => 'Unidad',
                'factor' => 1,
                'principal' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('productos', function (Blueprint $table) {
            $table->boolean('es_pesable')->default(false)->after('unidad_medida_id');
            $table->decimal('iva_alicuota', 5, 2)->default(21)->after('precio_venta');
            $table->integer('punto_pedido')->default(0)->after('stock_minimo');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('pin_supervisor')->nullable()->after('password');
        });

        Schema::table('caja_sesiones', function (Blueprint $table) {
            $table->string('terminal', 40)->nullable()->after('user_id');
            $table->date('dia_comercial')->nullable()->after('terminal');
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->foreignId('caja_sesion_id')->nullable()->after('deposito_id')
                ->constrained('caja_sesiones')->nullOnDelete();
            $table->string('canal', 20)->default('mostrador')->after('estado'); // mostrador | backoffice
            $table->decimal('recibido', 12, 2)->nullable()->after('pagado');
            $table->decimal('vuelto', 12, 2)->nullable()->after('recibido');
        });

        // En el POS la mayoría de las ventas son a consumidor final (sin cliente).
        Schema::table('ventas', function (Blueprint $table) {
            $table->unsignedBigInteger('cliente_id')->nullable()->change();
        });

        Schema::table('ventas_detalle', function (Blueprint $table) {
            $table->decimal('cantidad', 12, 3)->change();
        });

        Schema::table('movimientos_stock', function (Blueprint $table) {
            $table->decimal('cantidad', 12, 3)->change();
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('caja_sesion_id');
            $table->dropColumn(['canal', 'recibido', 'vuelto']);
        });

        Schema::table('caja_sesiones', fn (Blueprint $t) => $t->dropColumn(['terminal', 'dia_comercial']));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('pin_supervisor'));

        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['es_pesable', 'iva_alicuota', 'punto_pedido']);
        });

        Schema::table('ventas_detalle', fn (Blueprint $t) => $t->integer('cantidad')->change());
        Schema::table('movimientos_stock', fn (Blueprint $t) => $t->integer('cantidad')->change());

        Schema::dropIfExists('producto_codigos');
    }
};
