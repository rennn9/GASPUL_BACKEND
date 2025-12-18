<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AntrianController;
use App\Http\Controllers\KonsultasiController;
use App\Http\Controllers\StatistikPelayananController;
use App\Http\Controllers\StatistikKonsultasiController;
use App\Http\Controllers\StatistikSurveyController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\MonitorSettingController;
use App\Http\Controllers\LayananPublikController;
use App\Http\Controllers\StandarPelayananController;
use App\Http\Controllers\SurveyTemplateController;
use App\Http\Controllers\SurveyQuestionController;

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
// ADMIN ROUTES (Protected)
// ===========================
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {

    // Dashboard
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    // Statistik
    Route::get('/statistik', fn() => redirect()->route('admin.statistik.pelayanan'))->name('statistik');

    Route::prefix('statistik')->name('statistik.')->group(function () {
        Route::get('/pelayanan', [StatistikPelayananController::class, 'index'])->name('pelayanan');
        Route::get('/konsultasi', [StatistikKonsultasiController::class, 'index'])->name('konsultasi');
        Route::get('/survey', [StatistikSurveyController::class, 'index'])->name('survey');
        Route::post('/survey/reset-periode', [StatistikSurveyController::class, 'resetPeriode'])->name('survey.resetPeriode');
        Route::get('/survey/download-excel', [StatistikSurveyController::class, 'downloadExcel'])->name('survey.downloadExcel');
    });

    // Antrian
    Route::post('/antrian/update-status', [AntrianController::class, 'updateStatus'])->name('antrian.updateStatus');
    Route::get('/antrian/table', [AntrianController::class, 'table'])->name('antrian.table');
    Route::get('/tiket/download/{filename}', [AntrianController::class, 'downloadPdf'])->name('antrian.download');
    Route::get('/antrian/download', [AntrianController::class, 'downloadPdfDaftar'])->name('antrian.download.daftar');
    Route::post('/antrian/delete', [AntrianController::class, 'delete'])->name('antrian.delete');

    // Monitor + Settings
    Route::middleware('role:superadmin,admin,operator')->group(function () {
        Route::get('/monitor', [AntrianController::class, 'monitor'])->name('monitor');
        Route::get('/monitor/data', [AntrianController::class, 'monitorData'])->name('monitor.data');
        Route::get('/monitor/settings', [MonitorSettingController::class, 'settings'])->name('monitor.settings');
        Route::post('/monitor/settings/update', [MonitorSettingController::class, 'update'])->name('monitor.settings.update');
        Route::post('/monitor/settings/reset', [MonitorSettingController::class, 'reset'])->name('monitor.settings.reset');
        Route::post('/monitor/settings/autosave', [MonitorSettingController::class, 'autosave'])->name('monitor.settings.autosave');
    });

    // Konsultasi
    Route::get('/konsultasi', [KonsultasiController::class, 'index'])->name('konsultasi');
    Route::get('/konsultasi/pdf', [KonsultasiController::class, 'downloadPdf'])->name('konsultasi.pdf');
    Route::get('/konsultasi/{id}', [KonsultasiController::class, 'show'])->name('konsultasi.show');
    Route::post('/konsultasi/status/{id}', [KonsultasiController::class, 'updateStatus'])->name('konsultasi.updateStatus');
    Route::delete('/konsultasi/{id}', [KonsultasiController::class, 'destroy'])->name('konsultasi.destroy');

    // Survey CRUD
    Route::prefix('survey')->name('survey.')->group(function () {
        Route::get('/', [SurveyController::class, 'index'])->name('index');
        Route::get('/create', [SurveyController::class, 'create'])->name('create');
        Route::post('/store', [SurveyController::class, 'store'])->name('store');
        Route::get('/download', [SurveyController::class, 'downloadPdf'])->name('download');
        Route::get('/{id}', [SurveyController::class, 'show'])->name('show');
        Route::delete('/{id}', [SurveyController::class, 'destroy'])->name('destroy');
    });

    // Multi Delete
    Route::delete('/multi-delete', [AdminController::class, 'multiDelete'])->name('multi_delete');

    // Superadmin Only
    Route::middleware('superadmin')->group(function () {
        Route::resource('users', UserController::class);
    });

    // Layanan Publik
    Route::get('/layanan-publik', [LayananPublikController::class, 'index'])->name('layanan.index');
    Route::get('/layanan-publik/{id}', [LayananPublikController::class, 'show'])->name('layanan.show');
    Route::delete('/layanan-publik/{id}', [LayananPublikController::class, 'destroy'])->name('layanan.destroy');
    Route::post('/layanan-publik/{id}/add-status', [LayananPublikController::class, 'addStatus'])->name('layanan.addStatus');
    Route::post('/layanan-publik/{id}/kirim-verifikasi', [LayananPublikController::class, 'kirimVerifikasi'])->name('layanan.kirimVerifikasi');
    Route::get('/layanan-publik/{id}/download-bukti-terima', [LayananPublikController::class, 'downloadBuktiTerima'])->name('layanan.downloadBuktiTerima');

    // =============================
    // Standar Pelayanan (Admin)
    // =============================
    Route::get('/standar-pelayanan', [StandarPelayananController::class, 'index'])->name('standar-pelayanan.index');
    Route::get('/standar-pelayanan/create', [StandarPelayananController::class, 'create'])->name('standar-pelayanan.create');
    Route::post('/standar-pelayanan', [StandarPelayananController::class, 'store'])->name('standar-pelayanan.store');
    Route::get('/standar-pelayanan/{id}/edit', [StandarPelayananController::class, 'edit'])->name('standar-pelayanan.edit');
    Route::delete('/standar-pelayanan/{id}', [StandarPelayananController::class, 'destroy'])->name('standar-pelayanan.destroy');

    // =============================
    // Survey Template Management (Superadmin & Admin Only)
    // =============================
    Route::middleware('role:superadmin,admin')->group(function () {
        // Template CRUD
        Route::resource('survey-templates', SurveyTemplateController::class)->except(['show']);
        Route::post('/survey-templates/{surveyTemplate}/activate', [SurveyTemplateController::class, 'activate'])
             ->name('survey-templates.activate');
        Route::post('/survey-templates/{surveyTemplate}/duplicate', [SurveyTemplateController::class, 'duplicate'])
             ->name('survey-templates.duplicate');
        Route::get('/survey-templates/{surveyTemplate}/preview', [SurveyTemplateController::class, 'preview'])
             ->name('survey-templates.preview');

        // Question & Option Management
        Route::get('/survey-questions/{template_id}', [SurveyQuestionController::class, 'index'])
             ->name('survey-questions.index');
        Route::post('/survey-questions', [SurveyQuestionController::class, 'store'])
             ->name('survey-questions.store');
        Route::put('/survey-questions/{id}', [SurveyQuestionController::class, 'update'])
             ->name('survey-questions.update');
        Route::delete('/survey-questions/{id}', [SurveyQuestionController::class, 'destroy'])
             ->name('survey-questions.destroy');
        Route::post('/survey-questions/reorder', [SurveyQuestionController::class, 'reorder'])
             ->name('survey-questions.reorder');

        // Option Management
        Route::post('/survey-options', [SurveyQuestionController::class, 'storeOption'])
             ->name('survey-options.store');
        Route::put('/survey-options/{id}', [SurveyQuestionController::class, 'updateOption'])
             ->name('survey-options.update');
        Route::delete('/survey-options/{id}', [SurveyQuestionController::class, 'destroyOption'])
             ->name('survey-options.destroy');
    });
});

// =============================
// API Publik: Standar Pelayanan
// =============================
Route::get('/standar-pelayanan', [StandarPelayananController::class, 'getFile']);
