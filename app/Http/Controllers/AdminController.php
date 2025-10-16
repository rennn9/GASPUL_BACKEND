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

        // Ambil data Antrian
        $antrian = Antrian::latest()->get();

        // Format tanggal dengan nama hari bahasa Indonesia
        $antrian->map(function($item) {
            $item->tanggal_formatted = Carbon::parse($item->tanggal_daftar)->translatedFormat('l, d/m/Y');
            return $item;
        });

        return view('admin.dashboard', compact('antrian'));
    }
}
