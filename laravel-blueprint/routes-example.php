<?php

use App\Http\Controllers\Admin\AdminPaymentController;
use App\Http\Controllers\Admin\RaffleAdminController;
use App\Http\Controllers\PublicRaffleController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicRaffleController::class, 'index'])->name('home');
Route::get('/rifas/{raffle:slug}', [PublicRaffleController::class, 'show'])->name('raffles.show');
Route::post('/rifas/{raffle:slug}/ordenes', [PublicRaffleController::class, 'storeOrder'])
    ->middleware(['throttle:checkout'])
    ->name('raffles.orders.store');
Route::get('/ordenes/{order}', [PublicRaffleController::class, 'confirmation'])->name('orders.confirmation');

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('rifas', RaffleAdminController::class);
    Route::post('rifas/{raffle}/toggle-sale', [RaffleAdminController::class, 'toggleSale'])->name('raffles.toggle-sale');
    Route::post('pagos/{order}/aprobar', [AdminPaymentController::class, 'approve'])->name('payments.approve');
    Route::post('pagos/{order}/rechazar', [AdminPaymentController::class, 'reject'])->name('payments.reject');
});
