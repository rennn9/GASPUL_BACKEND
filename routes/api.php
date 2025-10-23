<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AntrianController;
use App\Http\Controllers\StatistikPelayananController;
use App\Http\Controllers\KonsultasiController;
use Carbon\Carbon; // ✅ tambahkan ini

// Rute Antrian
Route::post('/antrian/submit', [AntrianController::class, 'submit']);

// Rute Statistik Pelayanan
Route::get('/statistik-pelayanan', [StatistikPelayananController::class, 'apiIndex']);

// Rute Konsultasi
Route::post('/konsultasi/store', [KonsultasiController::class, 'store']);

// Waktu Server
Route::get('/server-time', function () {
    $time = Carbon::now('Asia/Makassar');
    return response()->json([
        'server_time' => $time->toIso8601String(),
    ]);
});
