<?php

use App\Http\Controllers\ReposicionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('reposicion')->name('reposicion.')->group(function () {
    Route::get('/', [ReposicionController::class, 'index'])
        ->middleware('permiso:ordenes_compra.ver')->name('index');

    Route::post('/generar', [ReposicionController::class, 'generar'])
        ->middleware('permiso:ordenes_compra.crear')->name('generar');
});
