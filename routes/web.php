<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\ExpenseReportController;
use App\Http\Controllers\KendaraanController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\MileageController;
use App\Http\Controllers\OdometerLogController;
use App\Http\Controllers\PengelolaKendaraanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RiwayatServisController;
use App\Http\Controllers\ServiceApprovalController;
use App\Http\Controllers\ServiceReminderController;
use App\Http\Controllers\UserController;
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

    Route::resource('kendaraan', KendaraanController::class)
        ->except(['destroy']);

    // Penghapusan kendaraan hanya untuk Admin.
    Route::delete('/kendaraan/{kendaraan}', [KendaraanController::class, 'destroy'])
        ->middleware('role:ADMIN')
        ->name('kendaraan.destroy');

    Route::get('/pengelola/kendaraan', [PengelolaKendaraanController::class, 'index'])->name('pengelola.kendaraan');

    Route::get('/mileage', [MileageController::class, 'index'])->name('mileage.index');
    Route::get('/mileage/history', [MileageController::class, 'history'])->name('mileage.history');
    Route::get('/mileage/odometer', [MileageController::class, 'odometer'])->name('mileage.odometer');
    Route::post('/mileage/odometer', [MileageController::class, 'store'])->name('mileage.odometer.store');
    Route::post('/mileage/trip', [MileageController::class, 'storeTrip'])->name('mileage.trip.store');

    Route::get('/maintenance', [MaintenanceController::class, 'index'])->name('maintenance.index');
    Route::get('/maintenance/create', [MaintenanceController::class, 'create'])->name('maintenance.create');
    Route::post('/maintenance', [MaintenanceController::class, 'store'])->name('maintenance.store');
    Route::get('/maintenance/mileage/{kendaraan}', [MaintenanceController::class, 'mileageKendaraan'])->name('maintenance.mileage-kendaraan');
    Route::get('/maintenance/{pengajuan}', [MaintenanceController::class, 'show'])->name('maintenance.show');
});

Route::middleware('auth')->group(function () {
    Route::post('/odometer-log', [OdometerLogController::class, 'store'])->name('odometer-log.store');
    Route::get('/riwayat-servis', [RiwayatServisController::class, 'index'])->name('service-history.index');
    Route::get('/riwayat-servis/{riwayatServis}', [RiwayatServisController::class, 'show'])->name('service-history.show');
    Route::post('/riwayat-servis', [RiwayatServisController::class, 'store'])->name('riwayat-servis.store');
    Route::post('/pengajuan-servis/{id}/approve', [ServiceApprovalController::class, 'approve'])
        ->middleware('role:admin')
        ->name('pengajuan-servis.approve');
    Route::post('/pengajuan-servis/{id}/reject', [ServiceApprovalController::class, 'reject'])
        ->middleware('role:admin')
        ->name('pengajuan-servis.reject');
    Route::get('/laporan-pengeluaran', [ExpenseReportController::class, 'index'])
        ->middleware('role:ADMIN')
        ->name('laporan-pengeluaran.index');
    Route::get('/laporan-pengeluaran/excel', [ExpenseReportController::class, 'exportExcel'])
        ->middleware('role:admin')
        ->name('laporan-pengeluaran.excel');
    Route::get('/laporan-pengeluaran/pdf', [ExpenseReportController::class, 'exportPdf'])
        ->middleware('role:admin')
        ->name('laporan-pengeluaran.pdf');
    Route::get('/service-reminders', [ServiceReminderController::class, 'index'])->name('service-reminders.index');
    Route::get('/service-reminder', [ServiceReminderController::class, 'viewIndex'])->name('service-reminder.index');

    // Manajemen User hanya untuk Admin.
    Route::middleware('role:ADMIN')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('user.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('user.create');
        Route::post('/users', [UserController::class, 'store'])->name('user.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('user.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('user.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('user.destroy');

        Route::patch('/pengajuan/{pengajuan}/approve', [MaintenanceController::class, 'approve'])->name('pengajuan.approve');
        Route::patch('/pengajuan/{pengajuan}/reject', [MaintenanceController::class, 'reject'])->name('pengajuan.reject');
    });

    Route::post('/service-reminder/settings', [ServiceReminderController::class, 'updateSettings'])
        ->middleware('role:ADMIN')
        ->name('service-reminder.settings');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
