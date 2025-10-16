<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AntrianController;
use App\Http\Controllers\StatistikPelayananController;

// Rute Antrian
Route::post('/antrian/submit', [AntrianController::class, 'submit']);

// Rute Statistik Pelayanan
Route::get('/statistik-pelayanan', [StatistikPelayananController::class, 'index']);
