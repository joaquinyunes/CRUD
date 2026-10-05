<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nada que tenga historial se borra: se archiva.
 *
 * Proveedores y usuarios se borraban físicamente, y como sus comprobantes los
 * referencian con una clave foránea RESTRICT, la operación fallaba con un error
 * de base de datos apenas el registro tenía una compra o una venta encima.
 * Ahora tienen estado, como ya lo tenían clientes, productos y categorías.
 *
 * Se elimina además two_factor_enabled: era una columna sin ninguna
 * implementación detrás, y una bandera de seguridad que no hace nada es peor
 * que no tenerla.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->string('estado')->default('activo')->after('direccion');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('activo')->default(true)->after('role_id');
            $table->dropColumn('two_factor_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropColumn('estado');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('activo');
            $table->boolean('two_factor_enabled')->default(false)->after('role_id');
        });
    }
};
