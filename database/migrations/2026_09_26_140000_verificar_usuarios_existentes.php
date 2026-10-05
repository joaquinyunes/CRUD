<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * El modelo de usuario pasa a implementar MustVerifyEmail, así que el middleware
 * "verified" —que ya estaba puesto en todas las rutas— empieza a hacer efecto.
 *
 * Los usuarios que ya venían operando no tienen por qué quedar afuera de golpe:
 * se los da por verificados. Los que se creen de ahora en más pasan por el
 * circuito normal (el alta desde administración los crea ya verificados).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->whereNull('email_verified_at')->update([
            'email_verified_at' => now(),
        ]);
    }

    public function down(): void
    {
        // No se puede saber cuáles estaban sin verificar antes: no se revierte.
    }
};
