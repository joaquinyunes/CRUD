<?php

use App\Http\Controllers\BackupController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('backup')->name('backup.')->group(function () {
    Route::get('/', [BackupController::class, 'index'])->name('index');
    Route::post('/crear', [BackupController::class, 'crear'])->name('crear');
    Route::post('/restaurar', [BackupController::class, 'restaurar'])->name('restaurar');
    Route::get('/descargar/{archivo}', [BackupController::class, 'descargar'])->name('descargar');
    Route::delete('/eliminar/{archivo}', [BackupController::class, 'eliminar'])->name('eliminar');
});
