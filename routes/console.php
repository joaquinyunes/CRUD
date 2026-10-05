<?php

use Illuminate\Support\Facades\Schedule;

/*
 * Tareas programadas.
 *
 * Las corre "php artisan schedule:work" (el contenedor lo levanta solo; ver
 * docker/entrypoint.sh) o un cron del sistema que llame a "schedule:run".
 */

// Los presupuestos vencen solos, sin que nadie tenga que acordarse.
Schedule::command('presupuestos:vencer')->dailyAt('01:00')->withoutOverlapping();

// Un aviso por día de lo que hay que reponer, en vez de depender de que alguien
// esté mirando la pantalla justo cuando una venta deja un producto en rojo.
Schedule::command('stock:avisar-critico')->dailyAt('08:00')->withoutOverlapping();

// Una copia por noche de toda la base, quedándose con las últimas dos semanas.
Schedule::command('backup:crear --conservar=14')->dailyAt('03:00')->withoutOverlapping();

// Higiene: tokens de API vencidos y jobs fallidos viejos no se acumulan.
Schedule::command('sanctum:prune-expired --hours=24')->daily();
Schedule::command('queue:prune-failed --hours=168')->daily();
