<?php

use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\MenuManagementController;
use App\Http\Controllers\TenantController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect()->route('kasir'))->name('home');

    Route::get('/kasir', fn () => view('cashier'))->name('kasir');
    Route::get('/riwayat', [HistoryController::class, 'index'])->name('riwayat');

    Route::get('/kelola', [TenantController::class, 'index'])->name('tenants.index');
    Route::post('/kelola/tenants', [TenantController::class, 'store'])->name('tenants.store');
    Route::patch('/kelola/tenants/{tenant}', [TenantController::class, 'update'])->name('tenants.update');
    Route::post('/kelola/tenants/{tenant}/toggle', [TenantController::class, 'toggle'])->name('tenants.toggle');

    Route::get('/kelola/menu', [MenuManagementController::class, 'index'])->name('menus.index');
    Route::patch('/kelola/menu/{menu}/harga', [MenuManagementController::class, 'updatePrice'])->name('menus.price');

    // JSON route berada di group web agar session auth + CSRF Laravel aktif.
    Route::get('/api/menus', [MenuController::class, 'index'])->name('api.menus.index');
    Route::post('/api/transactions', [TransactionController::class, 'store'])->name('api.transactions.store');
});
