<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AntrianController;
use App\Http\Controllers\StatistikPelayananController;
use App\Http\Controllers\KonsultasiController;
use App\Http\Controllers\SurveyController; // ✅ Tambahkan controller survey
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
Route::post('/survey', [SurveyController::class, 'store']);

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


