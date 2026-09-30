<?php

use App\Http\Controllers\KendaraanController;
use App\Http\Controllers\MileageController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::resource('kendaraan', KendaraanController::class);

    Route::get('/mileage', [MileageController::class, 'index'])->name('mileage.index');
    Route::get('/mileage/history', [MileageController::class, 'history'])->name('mileage.history');
    Route::get('/mileage/odometer', [MileageController::class, 'odometer'])->name('mileage.odometer');
    Route::post('/mileage/odometer', [MileageController::class, 'store'])->name('mileage.odometer.store');
    Route::post('/mileage/trip', [MileageController::class, 'storeTrip'])->name('mileage.trip.store');

    Route::get('/maintenance', function () {
        return view('maintenance.index');
    })->name('maintenance.index');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
