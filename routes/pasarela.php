<?php

use App\Http\Controllers\CobroQrController;
use App\Http\Controllers\ConciliacionController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/mercadopago', [WebhookController::class, 'mercadoPago'])->name('webhooks.mercadopago');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('pos/cobro-qr')->name('pos.cobro-qr.')->middleware('permiso:pos.usar')->group(function () {
        Route::post('/', [CobroQrController::class, 'crear'])->name('crear');
        Route::get('/{pago}', [CobroQrController::class, 'estado'])->name('estado');
        Route::post('/{pago}/cancelar', [CobroQrController::class, 'cancelar'])->name('cancelar');
    });

    Route::get('/reportes/conciliacion-mp', [ConciliacionController::class, 'index'])
        ->middleware('permiso:reportes.ver')->name('reportes.conciliacion-mp');
});
