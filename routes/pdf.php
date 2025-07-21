<?php

use App\Http\Controllers\PdfController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('pdf')->name('pdf.')->group(function () {
    Route::get('/venta/{id}', [PdfController::class, 'facturaVenta'])->name('venta');
    Route::get('/venta/{id}/boleta', [PdfController::class, 'boletaVenta'])->name('boleta');
    Route::get('/compra/{id}', [PdfController::class, 'facturaCompra'])->name('compra');
    Route::get('/catalogo', [PdfController::class, 'catalogoProductos'])->name('catalogo');
    Route::get('/stock', [PdfController::class, 'reporteStock'])->name('stock');
});
