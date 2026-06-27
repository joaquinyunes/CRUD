<?php

use App\Http\Controllers\AuditoriaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('auditoria')->name('auditoria.')->group(function () {
    Route::get('/', [AuditoriaController::class, 'index'])
        ->middleware('permiso:auditoria.ver')
        ->name('index');
});
