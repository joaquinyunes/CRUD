<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Doble factor por aplicación de autenticación (TOTP).
 *
 * El proyecto tenía una columna two_factor_enabled sin una sola línea de
 * implementación detrás, que se eliminó por eso mismo. Estas columnas sí tienen
 * el circuito completo: alta con QR, confirmación, códigos de recuperación y
 * verificación en el login.
 *
 * El secreto y los códigos de recuperación se guardan cifrados con APP_KEY: con
 * la base de datos en la mano, sin la clave de la aplicación, no sirven.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('two_factor_secret')->nullable()->after('password');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            // Solo cuenta como activo cuando el usuario demostró que su
            // aplicación genera códigos válidos. Si no, alcanzaba con empezar
            // el alta para quedarse afuera del sistema.
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
            ]);
        });
    }
};
