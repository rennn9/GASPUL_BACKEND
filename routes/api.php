<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AntrianController;
use App\Http\Controllers\StatistikPelayananController;
use App\Http\Controllers\KonsultasiController;

// Rute Antrian
Route::post('/antrian/submit', [AntrianController::class, 'submit']);

// Rute Statistik Pelayanan
Route::get('/statistik-pelayanan', [StatistikPelayananController::class, 'index']);

// Rute Konsultasi
Route::post('/konsultasi/store', [KonsultasiController::class, 'store']);
