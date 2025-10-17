<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Antrian;
use PDF;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function dashboard()
    {
        // Set locale Carbon ke Indonesia
        Carbon::setLocale('id');

        // Ambil data Antrian dengan pagination (20 data per halaman)
        $antrian = Antrian::orderBy('tanggal_daftar', 'desc')
                         ->orderBy('nomor_antrian', 'desc')
                         ->paginate(20);

        // Format tanggal dengan nama hari bahasa Indonesia
        $antrian->getCollection()->transform(function($item) {
            $item->tanggal_formatted = Carbon::parse($item->tanggal_daftar)->translatedFormat('l, d/m/Y');
            return $item;
        });

        return view('admin.dashboard', compact('antrian'));
    }
}
