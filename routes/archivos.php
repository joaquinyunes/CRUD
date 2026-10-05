<?php

use App\Http\Controllers\ArchivoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('archivos')->name('archivos.')->group(function () {
    Route::get('/', [ArchivoController::class, 'index'])
        ->middleware('permiso:archivos.ver')
        ->name('index');

    Route::post('/', [ArchivoController::class, 'store'])
        ->middleware('permiso:archivos.gestionar')
        ->name('store');

    Route::get('/{archivo}/descargar', [ArchivoController::class, 'download'])
        ->middleware('permiso:archivos.ver')
        ->name('download');

    Route::get('/{archivo}/ver', [ArchivoController::class, 'show'])
        ->middleware('permiso:archivos.ver')
        ->name('show');

    Route::delete('/{archivo}', [ArchivoController::class, 'destroy'])
        ->middleware('permiso:archivos.gestionar')
        ->name('destroy');
});
