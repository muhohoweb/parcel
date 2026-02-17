<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MpesaController;
use App\Http\Controllers\ParcelController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WhatsAppController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');


Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/parcels', [ParcelController::class, 'index'])->name('parcels.index');
    Route::post('/parcels', [ParcelController::class, 'store'])->name('parcels.store');
    Route::put('/parcels/{parcel}', [ParcelController::class, 'update'])->name('parcels.update');
    Route::delete('/parcels/{parcel}', [ParcelController::class, 'destroy'])->name('parcels.destroy');
    Route::post('/whatsapp/send-dispatch', [WhatsAppController::class, 'sendDispatchNotification']);
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
});
//WhatsApp callbacks
Route::match(['get', 'post'], '/whatsapp/webhook', [WhatsAppController::class, 'webhook']);



Route::post('/mpesa/callback', [MpesaController::class, 'callback'])->name('mpesa.callback');

require __DIR__ . '/settings.php';
