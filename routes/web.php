<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AntrianController;
use App\Http\Controllers\KonsultasiController;

// ===========================
// AUTH ROUTES
// ===========================
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit')->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ===========================
// ADMIN ROUTES (Hanya bisa diakses jika sudah login)
// ===========================
Route::middleware(['auth'])->prefix('admin')->group(function () {

    // Dashboard
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');

    // Statistik
    Route::get('/statistik', [AdminController::class, 'statistik'])->name('admin.statistik');

    // Multi delete
    Route::delete('/multi-delete', [AdminController::class, 'multiDelete'])->name('admin.multi_delete');

    // Update status via AJAX
    Route::post('/admin/antrian/update-status', [AntrianController::class, 'updateStatus'])
        ->name('admin.antrian.updateStatus');

    // Partial table Antrian untuk AJAX refresh
    Route::get('/admin/antrian/table', [AntrianController::class, 'table'])
        ->name('admin.antrian.table');

    // Download PDF tiket
    Route::get('admin/tiket/download/{filename}', [AntrianController::class, 'downloadPdf'])
        ->name('admin.antrian.download');

    // Konsultasi Routes
    Route::get('/konsultasi', [KonsultasiController::class, 'index'])->name('admin.konsultasi');
    Route::get('/konsultasi/{id}', [KonsultasiController::class, 'show'])->name('admin.konsultasi.show');
    Route::post('/konsultasi/status/{id}', [KonsultasiController::class, 'updateStatus'])->name('admin.konsultasi.updateStatus');
    Route::delete('/konsultasi/{id}', [KonsultasiController::class, 'destroy'])->name('admin.konsultasi.destroy');
});
