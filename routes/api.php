<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PengaduanMasyarakatController;
use App\Http\Controllers\PengaduanPelayananController;

Route::post('/pengaduan-masyarakat', [PengaduanMasyarakatController::class, 'store']);
Route::post('/pengaduan-pelayanan', [PengaduanPelayananController::class, 'store']);
