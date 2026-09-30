<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\ExpenseReportController;
use App\Http\Controllers\KendaraanController;
use App\Http\Controllers\OdometerLogController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RiwayatServisController;
use App\Http\Controllers\ServiceReminderController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', [AdminDashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/home', function () {
        return view('home');
    })->name('home');

    Route::resource('kendaraan', KendaraanController::class);

    Route::get('/maintenance', function () {
        return view('maintenance.index');
    })->name('maintenance.index');
});

Route::middleware('auth')->group(function () {
    Route::post('/odometer-log', [OdometerLogController::class, 'store'])->name('odometer-log.store');
    Route::post('/riwayat-servis', [RiwayatServisController::class, 'store'])->name('riwayat-servis.store');
    Route::get('/laporan-pengeluaran', [ExpenseReportController::class, 'index'])
        ->middleware('role:admin')
        ->name('laporan-pengeluaran.index');
    Route::get('/service-reminders', [ServiceReminderController::class, 'index'])->name('service-reminders.index');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
