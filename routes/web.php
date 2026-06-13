<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    // API Loker Pembayaran & Order
    Route::post('/api/create-order', [\App\Http\Controllers\LockerRentalController::class, 'createOrder'])->name('locker.create-order');
    Route::get('/api/pickup/{orderId}', [\App\Http\Controllers\LockerRentalController::class, 'getPickupCode'])->name('locker.pickup');
});

// Callback Webhook Notifikasi Midtrans (Publik & CSRF dikecualikan di bootstrap/app.php)
Route::post('/api/webhook', [\App\Http\Controllers\LockerRentalController::class, 'handleNotification'])->name('locker.payment-callback');

require __DIR__.'/settings.php';
