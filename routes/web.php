<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AntrianController;
use App\Http\Controllers\KonsultasiController;
use App\Http\Controllers\StatistikPelayananController;
use App\Http\Controllers\StatistikKonsultasiController;

// ===========================
// AUTH ROUTES
// ===========================
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout')
    ->middleware('auth');

// ===========================
// ADMIN ROUTES (Hanya bisa diakses jika sudah login)
// ===========================
Route::middleware(['auth'])->prefix('admin')->group(function () {

    // ======================
    // Dashboard
    // ======================
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');

    // ======================
    // Statistik
    // ======================
    // Redirect utama ke statistik pelayanan
    Route::get('/statistik', function () {
        return redirect()->route('admin.statistik.pelayanan');
    })->name('admin.statistik');

    Route::prefix('statistik')->group(function () {
        Route::get('/pelayanan', [StatistikPelayananController::class, 'index'])
            ->name('admin.statistik.pelayanan');

        Route::get('/konsultasi', [StatistikKonsultasiController::class, 'index'])
            ->name('admin.statistik.konsultasi');
    });

    // ======================
    // Antrian
    // ======================
    Route::post('/antrian/update-status', [AntrianController::class, 'updateStatus'])
        ->name('admin.antrian.updateStatus');

    Route::get('/antrian/table', [AntrianController::class, 'table'])
        ->name('admin.antrian.table');

    Route::get('/tiket/download/{filename}', [AntrianController::class, 'downloadPdf'])
        ->name('admin.antrian.download');

    // ======================
    // Konsultasi
    // ======================
    Route::get('/konsultasi', [KonsultasiController::class, 'index'])->name('admin.konsultasi');
    Route::get('/konsultasi/{id}', [KonsultasiController::class, 'show'])->name('admin.konsultasi.show');
    Route::post('/konsultasi/status/{id}', [KonsultasiController::class, 'updateStatus'])
        ->name('admin.konsultasi.updateStatus');
    Route::delete('/konsultasi/{id}', [KonsultasiController::class, 'destroy'])
        ->name('admin.konsultasi.destroy');

    // ======================
    // Multi Delete
    // ======================
    Route::delete('/multi-delete', [AdminController::class, 'multiDelete'])
        ->name('admin.multi_delete');
});
