<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\NumberController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\RaffleController as AdminRaffleController;
use App\Http\Controllers\Admin\RealtimeController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SystemHealthController;
use App\Http\Controllers\SuperAdmin\TenantController;
use App\Http\Controllers\Public\ConfirmationController;
use App\Http\Controllers\Public\PurchaseController;
use App\Http\Controllers\Public\RaffleController;
use Illuminate\Support\Facades\Route;

Route::get('/', [RaffleController::class, 'show'])->name('raffles.show');
Route::get('/rifas/{slug}', [RaffleController::class, 'show'])->name('raffles.slug');
Route::get('/rifas/{raffleId}/numeros-disponibles', [RaffleController::class, 'numbers'])->name('raffles.numbers');
Route::post('/rifas/{raffleId}/random', [PurchaseController::class, 'random'])->name('purchases.random');
Route::post('/rifas/{raffleId}/comprar', [PurchaseController::class, 'store'])->name('purchases.store');
Route::get('/confirmacion/{uuid}', [ConfirmationController::class, 'show'])->name('purchase.confirmation');

Route::prefix('admin')->name('admin.')->middleware('admin.basic')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/tiempo-real', RealtimeController::class)->name('realtime');
    Route::get('/rifas/crear', [AdminRaffleController::class, 'create'])->name('raffles.create');
    Route::post('/rifas', [AdminRaffleController::class, 'store'])->name('raffles.store');
    Route::get('/rifas/{raffle}/editar', [AdminRaffleController::class, 'edit'])->name('raffles.edit');
    Route::put('/rifas/{raffle}', [AdminRaffleController::class, 'update'])->name('raffles.update');
    Route::patch('/rifas/{raffle}/venta', [AdminRaffleController::class, 'toggleSale'])->name('raffles.toggle-sale');
    Route::delete('/rifas/{raffle}', [AdminRaffleController::class, 'destroy'])->name('raffles.destroy');
    Route::get('/pagos', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('/pagos/{order}', [PaymentController::class, 'show'])->name('payments.show');
    Route::post('/pagos/{order}/reenviar-correo', [PaymentController::class, 'resendEmail'])->name('payments.resend-email');
    Route::get('/pagos/{order}/aprobar', fn () => redirect()->route('admin.payments.index')->with('status', 'Para aprobar una compra usa el boton Aprobar desde el panel de pagos.'))->name('payments.approve.get');
    Route::post('/pagos/{order}/aprobar', [PaymentController::class, 'approve'])->name('payments.approve');
    Route::get('/pagos/{order}/rechazar', fn () => redirect()->route('admin.payments.index')->with('status', 'Para rechazar una compra usa el boton Rechazar desde el panel de pagos.'))->name('payments.reject.get');
    Route::post('/pagos/{order}/rechazar', [PaymentController::class, 'reject'])->name('payments.reject');
    Route::get('/reportes', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/salud', SystemHealthController::class)->name('health.index');
    Route::get('/numeros', [NumberController::class, 'index'])->name('numbers.index');
});

Route::prefix('superadmin')->name('superadmin.')->middleware('superadmin.basic')->group(function () {
    Route::get('/', [TenantController::class, 'index'])->name('tenants.index');
    Route::get('/tenants/create', [TenantController::class, 'create'])->name('tenants.create');
    Route::post('/tenants', [TenantController::class, 'store'])->name('tenants.store');
    Route::get('/tenants/{tenant}/edit', [TenantController::class, 'edit'])->name('tenants.edit');
    Route::put('/tenants/{tenant}', [TenantController::class, 'update'])->name('tenants.update');
    Route::patch('/tenants/{tenant}', [TenantController::class, 'update']);
    Route::delete('/tenants/{tenant}', [TenantController::class, 'destroy'])->name('tenants.destroy');
});



