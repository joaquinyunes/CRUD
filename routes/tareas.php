<?php

use App\Http\Controllers\TareaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('tareas')->name('tareas.')->group(function () {
    Route::get('/', [TareaController::class, 'index'])
        ->name('index');

    Route::get('/crear', [TareaController::class, 'create'])
        ->name('create');

    Route::post('/', [TareaController::class, 'store'])
        ->name('store');

    Route::get('/{tarea}/editar', [TareaController::class, 'edit'])
        ->name('edit');

    Route::put('/{tarea}', [TareaController::class, 'update'])
        ->name('update');

    Route::delete('/{tarea}', [TareaController::class, 'destroy'])
        ->name('destroy');

    Route::patch('/{tarea}/estado/{estado}', [TareaController::class, 'cambiarEstado'])
        ->name('cambiar-estado');
});
