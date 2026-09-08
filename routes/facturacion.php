<?php

use App\Http\Controllers\FacturacionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/ventas/{venta}/facturar', [FacturacionController::class, 'facturar'])
        ->middleware('permiso:ventas.crear')->name('ventas.facturar');

    Route::get('/reportes/libro-iva', [FacturacionController::class, 'libroIva'])
        ->middleware('permiso:reportes.ver')->name('reportes.libro-iva');
});
