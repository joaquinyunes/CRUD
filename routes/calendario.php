<?php

use App\Http\Controllers\CalendarioController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('calendario')->name('calendario.')->group(function () {
    Route::get('/', [CalendarioController::class, 'index'])
        ->name('index');

    Route::get('/eventos', [CalendarioController::class, 'eventosJson'])
        ->name('eventos-json');

    Route::post('/', [CalendarioController::class, 'store'])
        ->middleware('permiso:calendario.gestionar')
        ->name('store');

    Route::put('/{evento}', [CalendarioController::class, 'update'])
        ->middleware('permiso:calendario.gestionar')
        ->name('update');

    Route::delete('/{evento}', [CalendarioController::class, 'destroy'])
        ->middleware('permiso:calendario.gestionar')
        ->name('destroy');

    Route::patch('/{evento}/mover', [CalendarioController::class, 'mover'])
        ->middleware('permiso:calendario.gestionar')
        ->name('mover');
});
