<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AntrianController;
use App\Http\Controllers\KonsultasiController;
use App\Http\Controllers\StatistikPelayananController;
use App\Http\Controllers\StatistikKonsultasiController;
use App\Http\Controllers\UserController;

// ===========================
// AUTH ROUTES
// ===========================
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// ===========================
// ADMIN ROUTES (Protected by Auth)
// ===========================
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {

    // -----------------------
    // Dashboard
    // -----------------------
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    // -----------------------
    // Statistik
    // -----------------------
    Route::get('/statistik', fn() => redirect()->route('admin.statistik.pelayanan'))
        ->name('statistik');

    Route::prefix('statistik')->group(function () {
        Route::get('/pelayanan', [StatistikPelayananController::class, 'index'])
            ->name('statistik.pelayanan');
        Route::get('/konsultasi', [StatistikKonsultasiController::class, 'index'])
            ->name('statistik.konsultasi');
    });

    // -----------------------
    // Antrian
    // -----------------------
    Route::post('/antrian/update-status', [AntrianController::class, 'updateStatus'])
        ->name('antrian.updateStatus');

    Route::get('/antrian/table', [AntrianController::class, 'table'])
        ->name('antrian.table');

    Route::get('/tiket/download/{filename}', [AntrianController::class, 'downloadPdf'])
        ->name('antrian.download');

    Route::get('/antrian/download', [AntrianController::class, 'downloadPdfDaftar'])
        ->name('antrian.download.daftar');

    // -----------------------
    // Konsultasi
    // -----------------------
// ✅ Urutan yang benar
Route::get('/konsultasi', [KonsultasiController::class, 'index'])->name('konsultasi');
Route::get('/konsultasi/pdf', [KonsultasiController::class, 'downloadPdf'])->name('konsultasi.pdf');
Route::get('/konsultasi/{id}', [KonsultasiController::class, 'show'])->name('konsultasi.show');
Route::post('/konsultasi/status/{id}', [KonsultasiController::class, 'updateStatus'])->name('konsultasi.updateStatus');
Route::delete('/konsultasi/{id}', [KonsultasiController::class, 'destroy'])->name('konsultasi.destroy');


    // -----------------------
    // Multi Delete
    // -----------------------
    Route::delete('/multi-delete', [AdminController::class, 'multiDelete'])
        ->name('multi_delete');

    // -----------------------
    // Superadmin Only Routes
    // -----------------------
Route::middleware('superadmin')->group(function () {
    Route::resource('users', \App\Http\Controllers\UserController::class);
});

});
