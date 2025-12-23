<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AntrianController;
use App\Http\Controllers\StatistikPelayananController;
use App\Http\Controllers\KonsultasiController;
use App\Http\Controllers\SurveyController; // ✅ Tambahkan controller survey
use App\Http\Controllers\Api\SurveyApiController;
use Carbon\Carbon;
use App\Http\Controllers\LayananPublikController;


// ===============================
// 📌 RUTE ANTRIAN
// ===============================
Route::post('/antrian/submit', [AntrianController::class, 'submit']);

// ===============================
// 📊 RUTE STATISTIK PELAYANAN
// ===============================
Route::get('/statistik-pelayanan', [StatistikPelayananController::class, 'apiIndex']);

// ===============================
// 💬 RUTE KONSULTASI
// ===============================
Route::post('/konsultasi/store', [KonsultasiController::class, 'store']);

// ===============================
// 🧾 RUTE SURVEY (Baru Ditambahkan)
// ===============================
Route::get('/survey/questions', [SurveyApiController::class, 'getQuestions']);
Route::post('/survey', [SurveyController::class, 'store']);

// ===============================
// 📋 RUTE SURVEY TEMPLATES (Dynamic Survey)
// ===============================
use App\Http\Controllers\SurveyTemplateController;

Route::get('/survey-templates', [SurveyTemplateController::class, 'index']);
Route::get('/survey-templates/active', [SurveyTemplateController::class, 'getActiveTemplate']);
Route::get('/survey-templates/{id}', [SurveyTemplateController::class, 'show']);

// ===============================
// 🕓 WAKTU SERVER
// ===============================
Route::get('/server-time', function () {
    $time = Carbon::now('Asia/Makassar');
    return response()->json([
        'server_time' => $time->toIso8601String(),
    ]);
});

// ===============================
// ✅ ANTRIAN SELESAI HARI INI
// ===============================
Route::get('/antrian/selesai-hari-ini', [AntrianController::class, 'selesaiHariIni']);

// ===============================
// 📢LAYANAN PUBLIK
// ===============================
Route::post('/layanan', [LayananPublikController::class, 'store']);
Route::get('/layanan/generate-no-registrasi', [LayananPublikController::class, 'generateNomorRegistrasi']);

// ===============================
// 🔍 CEK STATUS PENGAJUAN
// ===============================
use App\Http\Controllers\StatusLayananController;

Route::get('/cek-status', [StatusLayananController::class, 'cekStatus']);

// ===============================
// ✅ FILE STANDAR PELAYANAN
// ===============================
use App\Http\Controllers\StandarPelayananController;

Route::get('/standar-pelayanan', [StandarPelayananController::class, 'getFile']);


