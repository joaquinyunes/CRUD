<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->foreignId('proveedor_id')->nullable()->after('categoria_id')
                ->constrained('proveedores')->nullOnDelete();
        });

        // Backfill: proveedor de la última compra de cada producto.
        $ultimos = DB::table('compras_detalle')
            ->join('compras', 'compras_detalle.compra_id', '=', 'compras.id')
            ->select('compras_detalle.producto_id', DB::raw('MAX(compras.id) as compra_id'))
            ->groupBy('compras_detalle.producto_id')
            ->get();

        foreach ($ultimos as $row) {
            $proveedorId = DB::table('compras')->where('id', $row->compra_id)->value('proveedor_id');
            if ($proveedorId) {
                DB::table('productos')->where('id', $row->producto_id)->update(['proveedor_id' => $proveedorId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('productos', fn (Blueprint $t) => $t->dropConstrainedForeignId('proveedor_id'));
    }
};
