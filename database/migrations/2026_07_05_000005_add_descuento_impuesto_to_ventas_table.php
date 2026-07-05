<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->decimal('subtotal', 12, 2)->default(0)->after('total');
            $table->decimal('descuento', 12, 2)->default(0)->after('subtotal');
            $table->string('descuento_tipo', 20)->nullable()->after('descuento'); // porcentaje | fijo
            $table->decimal('impuesto', 12, 2)->default(0)->after('descuento');
            $table->decimal('total_final', 12, 2)->default(0)->after('impuesto');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'descuento', 'descuento_tipo', 'impuesto', 'total_final']);
        });
    }
};
