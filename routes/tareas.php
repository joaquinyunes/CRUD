<?php

use App\Http\Controllers\TareaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('tareas')->name('tareas.')->group(function () {
    Route::get('/', [TareaController::class, 'index'])
        ->name('index');

    Route::get('/crear', [TareaController::class, 'create'])
        ->middleware('permiso:tareas.gestionar')
        ->name('create');

    Route::post('/', [TareaController::class, 'store'])
        ->middleware('permiso:tareas.gestionar')
        ->name('store');

    Route::get('/{tarea}/editar', [TareaController::class, 'edit'])
        ->middleware('permiso:tareas.gestionar')
        ->name('edit');

    Route::put('/{tarea}', [TareaController::class, 'update'])
        ->middleware('permiso:tareas.gestionar')
        ->name('update');

    Route::delete('/{tarea}', [TareaController::class, 'destroy'])
        ->middleware('permiso:tareas.gestionar')
        ->name('destroy');

    Route::patch('/{tarea}/estado/{estado}', [TareaController::class, 'cambiarEstado'])
        ->middleware('permiso:tareas.gestionar')
        ->name('cambiar-estado');
});
