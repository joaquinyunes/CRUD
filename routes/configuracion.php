<?php

use App\Http\Controllers\ConfiguracionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('configuracion')->name('configuracion.')->group(function () {
    Route::get('/', [ConfiguracionController::class, 'index'])
        ->middleware('permiso:configuracion.ver')
        ->name('index');
    Route::post('/', [ConfiguracionController::class, 'update'])
        ->middleware('permiso:configuracion.editar')
        ->name('update');
});
