<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Fase 2 (migracion a MongoDB): clientes y proveedores pasan a Mongo, asi que
// sus FK en SQL dejan de poder ser bigint con constraint -> pasan a string
// (ObjectId hex de 24 caracteres). Los datos existentes se migran con el
// comando `catalogo:migrar-a-mongo` (ver app/Console/Commands).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
        });
        Schema::table('ventas', function (Blueprint $table) {
            $table->string('cliente_id', 24)->nullable()->change();
        });

        Schema::table('presupuestos', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
        });
        Schema::table('presupuestos', function (Blueprint $table) {
            $table->string('cliente_id', 24)->nullable()->change();
        });

        Schema::table('compras', function (Blueprint $table) {
            $table->dropForeign(['proveedor_id']);
        });
        Schema::table('compras', function (Blueprint $table) {
            $table->string('proveedor_id', 24)->nullable()->change();
        });

        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->dropForeign(['proveedor_id']);
        });
        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->string('proveedor_id', 24)->nullable()->change();
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->dropForeign(['proveedor_id']);
        });
        Schema::table('productos', function (Blueprint $table) {
            $table->string('proveedor_id', 24)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->unsignedBigInteger('cliente_id')->change();
        });
        Schema::table('ventas', function (Blueprint $table) {
            $table->foreign('cliente_id')->references('id')->on('clientes');
        });

        Schema::table('presupuestos', function (Blueprint $table) {
            $table->unsignedBigInteger('cliente_id')->change();
        });
        Schema::table('presupuestos', function (Blueprint $table) {
            $table->foreign('cliente_id')->references('id')->on('clientes');
        });

        Schema::table('compras', function (Blueprint $table) {
            $table->unsignedBigInteger('proveedor_id')->change();
        });
        Schema::table('compras', function (Blueprint $table) {
            $table->foreign('proveedor_id')->references('id')->on('proveedores');
        });

        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->unsignedBigInteger('proveedor_id')->change();
        });
        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->foreign('proveedor_id')->references('id')->on('proveedores');
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->unsignedBigInteger('proveedor_id')->nullable()->change();
        });
        Schema::table('productos', function (Blueprint $table) {
            $table->foreign('proveedor_id')->references('id')->on('proveedores')->nullOnDelete();
        });
    }
};
