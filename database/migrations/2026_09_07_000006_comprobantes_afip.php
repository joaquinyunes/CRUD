<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comprobantes_afip', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            $table->unsignedTinyInteger('tipo_comprobante'); // 1=A 6=B 11=C ...
            $table->unsignedInteger('punto_venta');
            $table->unsignedBigInteger('numero')->nullable();
            $table->string('cae', 20)->nullable();
            $table->date('cae_vencimiento')->nullable();
            $table->decimal('importe_total', 14, 2);
            $table->decimal('importe_neto', 14, 2)->default(0);
            $table->decimal('importe_iva', 14, 2)->default(0);
            $table->unsignedTinyInteger('doc_tipo')->default(99); // 80=CUIT 96=DNI 99=CF
            $table->string('doc_nro', 20)->default('0');
            $table->string('resultado', 12)->default('pendiente'); // A | R | simulado | pendiente | error
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->index(['tipo_comprobante', 'punto_venta', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comprobantes_afip');
    }
};
