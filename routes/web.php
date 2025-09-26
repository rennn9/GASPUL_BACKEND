<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PengaduanMasyarakatController;
use App\Http\Controllers\PengaduanPelayananController;
use App\Http\Controllers\AuthController;

// Login & logout
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Admin
Route::prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');

    Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');

    // CRUD
    Route::resource('pengaduan_masyarakat', PengaduanMasyarakatController::class);
    Route::resource('pengaduan_pelayanan', PengaduanPelayananController::class);

    // Multi delete
    Route::delete('/multi-delete', [AdminController::class, 'multiDelete'])->name('admin.multi_delete');

    // Export PDF
    Route::get('/pengaduan-masyarakat/pdf', [AdminController::class, 'exportMasyarakatPdf'])->name('admin.pengaduan_masyarakat.pdf');
    Route::get('/pengaduan-pelayanan/pdf', [AdminController::class, 'exportPelayananPdf'])->name('admin.pengaduan_pelayanan.pdf');

    // Filter Pengaduan Masyarakat
    Route::get('/admin/filter-masyarakat', [AdminController::class, 'filterMasyarakat'])
    ->name('admin.filter.masyarakat');

    // Filter Pengaduan Pelayanan
    Route::get('/admin/filter-pelayanan', [AdminController::class, 'filterPelayanan'])
        ->name('admin.filter.pelayanan');

});
