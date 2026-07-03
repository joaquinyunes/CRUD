<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\CompraController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\ProveedorController;
use App\Http\Controllers\Api\VentaController;
use Illuminate\Support\Facades\Route;

// Auth (público)
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas con token
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Productos
    Route::apiResource('productos', ProductoController::class)->except(['edit', 'create']);

    // Clientes
    Route::apiResource('clientes', ClienteController::class)->except(['edit', 'create']);

    // Categorías
    Route::apiResource('categorias', CategoriaController::class)->except(['edit', 'create']);

    // Proveedores
    Route::apiResource('proveedores', ProveedorController::class)->except(['edit', 'create']);

    // Ventas
    Route::apiResource('ventas', VentaController::class)->except(['edit', 'create', 'update']);

    // Compras
    Route::apiResource('compras', CompraController::class)->except(['edit', 'create', 'update']);
});
