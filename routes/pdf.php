<?php

use App\Http\Controllers\PdfController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('pdf')->name('pdf.')->group(function () {
    Route::get('/venta/{id}', [PdfController::class, 'facturaVenta'])
        ->middleware('permiso:ventas.ver')
        ->name('venta');
    Route::get('/venta/{id}/boleta', [PdfController::class, 'boletaVenta'])
        ->middleware('permiso:ventas.ver')
        ->name('boleta');
    Route::get('/compra/{id}', [PdfController::class, 'facturaCompra'])
        ->middleware('permiso:compras.ver')
        ->name('compra');
    Route::get('/catalogo', [PdfController::class, 'catalogoProductos'])
        ->middleware('permiso:productos.ver')
        ->name('catalogo');
    Route::get('/stock', [PdfController::class, 'reporteStock'])
        ->middleware('permiso:stock.ver')
        ->name('stock');
});
