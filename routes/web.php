<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AntrianController;
use App\Http\Controllers\KonsultasiController;
use App\Http\Controllers\StatistikPelayananController;
use App\Http\Controllers\StatistikKonsultasiController;
use App\Http\Controllers\StatistikSurveyController; // Statistik Survey (IKM)
use App\Http\Controllers\UserController;
use App\Http\Controllers\SurveyController;

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

    Route::prefix('statistik')->name('statistik.')->group(function () {

        // Pelayanan
        Route::get('/pelayanan', [StatistikPelayananController::class, 'index'])
            ->name('pelayanan');

        // Konsultasi
        Route::get('/konsultasi', [StatistikKonsultasiController::class, 'index'])
            ->name('konsultasi');

        // Survey (IKM)
        Route::get('/survey', [StatistikSurveyController::class, 'index'])
            ->name('survey');

        // Reset Periode Survey
        Route::post('/survey/reset-periode', [StatistikSurveyController::class, 'resetPeriode'])
            ->name('survey.resetPeriode');

        // Download Excel Survey
        Route::get('/survey/download-excel', [StatistikSurveyController::class, 'downloadExcel'])
            ->name('survey.downloadExcel');
            
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

    Route::post('/antrian/delete', [AntrianController::class, 'delete'])
        ->name('antrian.delete');

    // -----------------------
    // Monitor Antrian
    // -----------------------
    Route::middleware('role:superadmin,admin,operator')->group(function () {
        Route::get('/monitor', [AntrianController::class, 'monitor'])->name('monitor');
        Route::get('/monitor/data', [AntrianController::class, 'monitorData'])->name('monitor.data');
    });

    // -----------------------
    // Konsultasi
    // -----------------------
    Route::get('/konsultasi', [KonsultasiController::class, 'index'])->name('konsultasi');
    Route::get('/konsultasi/pdf', [KonsultasiController::class, 'downloadPdf'])->name('konsultasi.pdf');
    Route::get('/konsultasi/{id}', [KonsultasiController::class, 'show'])->name('konsultasi.show');
    Route::post('/konsultasi/status/{id}', [KonsultasiController::class, 'updateStatus'])->name('konsultasi.updateStatus');
    Route::delete('/konsultasi/{id}', [KonsultasiController::class, 'destroy'])->name('konsultasi.destroy');

    // -----------------------
    // Survey CRUD
    // -----------------------
    Route::prefix('survey')->name('survey.')->group(function () {
        Route::get('/', [SurveyController::class, 'index'])->name('index');
        Route::get('/create', [SurveyController::class, 'create'])->name('create');
        Route::post('/store', [SurveyController::class, 'store'])->name('store');
        Route::get('/download', [SurveyController::class, 'downloadPdf'])->name('download');
        Route::get('/{id}', [SurveyController::class, 'show'])->name('show');
        Route::delete('/{id}', [SurveyController::class, 'destroy'])->name('destroy');
    });

    // -----------------------
    // Multi Delete
    // -----------------------
    Route::delete('/multi-delete', [AdminController::class, 'multiDelete'])
        ->name('multi_delete');

    // -----------------------
    // Superadmin Only
    // -----------------------
    Route::middleware('superadmin')->group(function () {
        Route::resource('users', UserController::class);
    });
});
