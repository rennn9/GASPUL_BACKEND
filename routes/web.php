<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PengaduanMasyarakatController;
use App\Http\Controllers\PengaduanPelayananController;
use App\Http\Controllers\AuthController;

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

    // CRUD
    Route::resource('pengaduan_masyarakat', PengaduanMasyarakatController::class);
    Route::resource('pengaduan_pelayanan', PengaduanPelayananController::class);

    // Multi delete
    Route::delete('/multi-delete', [AdminController::class, 'multiDelete'])->name('admin.multi_delete');

    // Export PDF
    Route::get('/pengaduan-masyarakat/pdf', [AdminController::class, 'exportMasyarakatPdf'])->name('admin.pengaduan_masyarakat.pdf');
    Route::get('/pengaduan-pelayanan/pdf', [AdminController::class, 'exportPelayananPdf'])->name('admin.pengaduan_pelayanan.pdf');

    // Filter AJAX
    Route::get('/filter-masyarakat', [AdminController::class, 'filterMasyarakat'])->name('admin.filter.masyarakat');
    Route::get('/filter-pelayanan', [AdminController::class, 'filterPelayanan'])->name('admin.filter.pelayanan');
});
