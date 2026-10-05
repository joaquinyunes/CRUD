<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Decide si corresponde cargar los datos de demostración.
 *
 * Antes esto vivía en el entrypoint del contenedor, que contaba los usuarios
 * con "php artisan tinker --execute" y, si el comando fallaba por lo que fuera,
 * asumía que la base estaba vacía y sembraba igual. Como DemoSeeder borra y
 * regenera las tablas de negocio, ese default se llevaba puestos los datos de
 * una instalación real ante cualquier error transitorio.
 *
 * Acá la decisión se toma en PHP y el default es el contrario: ante la duda, no
 * se siembra y el comando lo dice.
 */
class SembrarDemo extends Command
{
    protected $signature = 'demo:sembrar
                            {--forzar : Sembrar aunque la base ya tenga datos (los borra)}';

    protected $description = 'Carga los datos de demostración solo si la base está vacía.';

    public function handle(): int
    {
        if ($this->option('forzar')) {
            $this->warn('Sembrando por --forzar: se descartan los datos de negocio existentes.');

            return $this->sembrar();
        }

        try {
            if (! Schema::hasTable('users')) {
                $this->info('Todavía no hay tabla de usuarios; se siembra.');

                return $this->sembrar();
            }

            $usuarios = DB::table('users')->count();
        } catch (Throwable $e) {
            // No se pudo saber si hay datos: no se toca nada. Sembrar a ciegas
            // sería destruir la base de un cliente por un error de conexión.
            $this->error('No se pudo verificar el estado de la base: '.$e->getMessage());
            $this->error('No se siembra nada. Si es una instalación nueva, correr con --forzar.');

            return self::FAILURE;
        }

        if ($usuarios > 0) {
            $this->info("La base ya tiene {$usuarios} usuarios; se omiten los datos de demostración.");

            return self::SUCCESS;
        }

        return $this->sembrar();
    }

    private function sembrar(): int
    {
        // DemoSeeder crea ventas y compras, y esas necesitan un usuario y un rol
        // que existan: los datos base van primero, siempre. Es idempotente.
        $this->call('db:seed', ['--force' => true]);
        $this->call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);

        return self::SUCCESS;
    }
}
