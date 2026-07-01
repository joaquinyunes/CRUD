<?php

use App\Http\Controllers\ExportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('exportar')->name('exportar.')->group(function () {
    Route::get('/productos', [ExportController::class, 'productos'])
        ->middleware('permiso:productos.exportar')
        ->name('productos');

    Route::get('/ventas', [ExportController::class, 'ventas'])
        ->middleware('permiso:ventas.exportar')
        ->name('ventas');
});
