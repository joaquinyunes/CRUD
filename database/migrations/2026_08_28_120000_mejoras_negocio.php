<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->decimal('pagado', 12, 2)->default(0)->after('total_final');
            $table->string('estado_pago', 20)->default('impago')->after('pagado'); // impago | parcial | pagado
            $table->string('motivo_anulacion')->nullable()->after('estado');
            $table->boolean('stock_aplicado')->default(false)->after('estado');
            $table->index(['estado', 'fecha']);
        });

        Schema::table('compras', function (Blueprint $table) {
            $table->decimal('pagado', 12, 2)->default(0)->after('total_final');
            $table->string('estado_pago', 20)->default('impago')->after('pagado');
            $table->string('motivo_anulacion')->nullable()->after('estado');
            $table->boolean('stock_aplicado')->default(false)->after('estado');
            $table->index(['estado', 'fecha']);
        });

        Schema::table('ventas_detalle', function (Blueprint $table) {
            $table->decimal('costo_unitario', 12, 2)->default(0)->after('precio');
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->string('codigo_barra')->nullable()->after('codigo');
            $table->index('codigo_barra');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropIndex(['estado', 'fecha']);
            $table->dropColumn(['pagado', 'estado_pago', 'motivo_anulacion', 'stock_aplicado']);
        });

        Schema::table('compras', function (Blueprint $table) {
            $table->dropIndex(['estado', 'fecha']);
            $table->dropColumn(['pagado', 'estado_pago', 'motivo_anulacion', 'stock_aplicado']);
        });

        Schema::table('ventas_detalle', function (Blueprint $table) {
            $table->dropColumn('costo_unitario');
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->dropIndex(['codigo_barra']);
            $table->dropColumn('codigo_barra');
        });
    }
};
