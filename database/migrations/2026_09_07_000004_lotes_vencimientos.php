<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_lotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('deposito_id')->constrained('depositos')->cascadeOnDelete();
            $table->string('lote', 60)->nullable();
            $table->date('vencimiento')->nullable();
            $table->decimal('cantidad', 12, 3)->default(0);
            $table->timestamps();
            $table->index(['producto_id', 'vencimiento']);
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->boolean('controla_vencimiento')->default(false)->after('es_pesable');
            $table->unsignedSmallInteger('dias_alerta_vencimiento')->default(30)->after('controla_vencimiento');
        });
    }

    public function down(): void
    {
        Schema::table('productos', fn (Blueprint $t) => $t->dropColumn(['controla_vencimiento', 'dias_alerta_vencimiento']));
        Schema::dropIfExists('producto_lotes');
    }
};
