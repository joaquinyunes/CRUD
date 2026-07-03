<?php

use App\Http\Controllers\NotificacionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('notificaciones')->name('notificaciones.')->group(function () {
    Route::get('/', [NotificacionController::class, 'index'])
        ->name('index');

    Route::patch('/{notificacion}/leer', [NotificacionController::class, 'marcarLeida'])
        ->name('marcar-leida');

    Route::patch('/leer-todas', [NotificacionController::class, 'marcarTodasLeidas'])
        ->name('leer-todas');

    Route::get('/count', [NotificacionController::class, 'noLeidasCount'])
        ->name('count');
});
