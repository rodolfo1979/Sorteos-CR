<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\NumberController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\RaffleController as AdminRaffleController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Public\ConfirmationController;
use App\Http\Controllers\Public\PurchaseController;
use App\Http\Controllers\Public\RaffleController;
use Illuminate\Support\Facades\Route;

Route::get('/', [RaffleController::class, 'show'])->name('raffles.show');
Route::get('/rifas/{slug}', [RaffleController::class, 'show'])->name('raffles.slug');
Route::post('/rifas/{raffle}/random', [PurchaseController::class, 'random'])->name('purchases.random');
Route::post('/rifas/{raffle}/comprar', [PurchaseController::class, 'store'])->name('purchases.store');
Route::get('/confirmacion/{uuid}', [ConfirmationController::class, 'show'])->name('purchase.confirmation');

Route::prefix('admin')->name('admin.')->middleware('admin.basic')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/rifas/{raffle}/editar', [AdminRaffleController::class, 'edit'])->name('raffles.edit');
    Route::put('/rifas/{raffle}', [AdminRaffleController::class, 'update'])->name('raffles.update');
    Route::get('/pagos', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('/pagos/{order}/aprobar', [PaymentController::class, 'approve'])->name('payments.approve');
    Route::post('/pagos/{order}/rechazar', [PaymentController::class, 'reject'])->name('payments.reject');
    Route::get('/reportes', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/numeros', [NumberController::class, 'index'])->name('numbers.index');
});

