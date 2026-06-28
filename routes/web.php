<?php

use App\Http\Controllers\LockerRentalController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    // API Loker Pembayaran & Order
    Route::post('/api/create-order', [LockerRentalController::class, 'createOrder'])->name('locker.create-order');
    Route::get('/api/pickup/{orderId}', [LockerRentalController::class, 'getPickupCode'])->name('locker.pickup');
});

// Callback Webhook Notifikasi Midtrans (Publik & CSRF dikecualikan di bootstrap/app.php)
Route::post('/api/webhook', [LockerRentalController::class, 'handleNotification'])->name('locker.payment-callback');

// Endpoint Tap Kartu RFID Fisik dari Arduino (Publik & CSRF dikecualikan di bootstrap/app.php)
Route::post('/api/arduino/tap-card', [LockerRentalController::class, 'tapCard'])->name('locker.arduino-tap');

require __DIR__.'/settings.php';
