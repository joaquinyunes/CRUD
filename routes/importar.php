<?php

use App\Http\Controllers\ImportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('importar')->name('importar.')->group(function () {
    Route::get('/', [ImportController::class, 'index'])
        ->middleware('permiso:importar.ejecutar')
        ->name('index');
    Route::post('/', [ImportController::class, 'importar'])
        ->middleware('permiso:importar.ejecutar')
        ->name('importar');
});
