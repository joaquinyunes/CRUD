<?php

use App\Http\Controllers\EtiquetaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'permiso:productos.ver'])->prefix('etiquetas')->name('etiquetas.')->group(function () {
    Route::get('/', [EtiquetaController::class, 'index'])->name('index');
    Route::match(['get', 'post'], '/imprimir', [EtiquetaController::class, 'imprimir'])->name('imprimir');
});
