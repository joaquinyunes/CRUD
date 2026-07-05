<?php

use App\Http\Controllers\ConfiguracionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('configuracion')->name('configuracion.')->group(function () {
    Route::get('/', [ConfiguracionController::class, 'index'])->name('index');
    Route::post('/', [ConfiguracionController::class, 'update'])->name('update');
});
