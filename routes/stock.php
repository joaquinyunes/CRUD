<?php

use App\Http\Controllers\StockController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('stock')->name('stock.')->group(function () {
    Route::get('/', [StockController::class, 'index'])
        ->middleware('permiso:stock.ver')
        ->name('index');
});
