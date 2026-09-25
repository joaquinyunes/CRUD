<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depositos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('direccion')->nullable();
            $table->boolean('es_principal')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('stock_deposito', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('deposito_id')->constrained('depositos')->cascadeOnDelete();
            $table->integer('cantidad')->default(0);
            $table->unique(['producto_id', 'deposito_id']);
        });

        Schema::table('movimientos_stock', function (Blueprint $table) {
            $table->foreignId('deposito_id')->nullable()->after('producto_id')->constrained('depositos')->nullOnDelete();
            // Amplía el enum original para admitir transferencias entre depósitos.
            $table->string('tipo', 30)->change();
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->foreignId('deposito_id')->nullable()->after('cliente_id')->constrained('depositos')->nullOnDelete();
        });

        Schema::table('compras', function (Blueprint $table) {
            $table->foreignId('deposito_id')->nullable()->after('proveedor_id')->constrained('depositos')->nullOnDelete();
        });

        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->foreignId('deposito_id')->nullable()->after('proveedor_id')->constrained('depositos')->nullOnDelete();
        });

        // Depósito principal + backfill del stock actual de cada producto.
        $id = DB::table('depositos')->insertGetId([
            'nombre'       => 'Depósito principal',
            'es_principal' => true,
            'activo'       => true,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        foreach (DB::table('productos')->select('id', 'stock')->get() as $p) {
            DB::table('stock_deposito')->insert([
                'producto_id' => $p->id,
                'deposito_id' => $id,
                'cantidad'    => $p->stock,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('ordenes_compra', fn (Blueprint $t) => $t->dropConstrainedForeignId('deposito_id'));
        Schema::table('compras', fn (Blueprint $t) => $t->dropConstrainedForeignId('deposito_id'));
        Schema::table('ventas', fn (Blueprint $t) => $t->dropConstrainedForeignId('deposito_id'));
        Schema::table('movimientos_stock', fn (Blueprint $t) => $t->dropConstrainedForeignId('deposito_id'));
        Schema::dropIfExists('stock_deposito');
        Schema::dropIfExists('depositos');
    }
};
