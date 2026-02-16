<?php

use App\Http\Controllers\MpesaController;
use App\Http\Controllers\ParcelController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');




Route::get('/parcels', [ParcelController::class, 'index'])->name('parcels.index');
Route::post('/parcels', [ParcelController::class, 'store'])->name('parcels.store');
Route::put('/parcels/{parcel}', [ParcelController::class, 'update'])->name('parcels.update');
Route::delete('/parcels/{parcel}', [ParcelController::class, 'destroy'])->name('parcels.destroy');


Route::post('/mpesa/callback', [MpesaController::class, 'callback'])->name('mpesa.callback');

require __DIR__.'/settings.php';
