<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Antrian;
use PDF;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function dashboard()
    {
        // Set locale Carbon ke Indonesia
        Carbon::setLocale('id');

        // Ambil data Antrian dengan pagination (20 data per halaman)
        $antrian = Antrian::orderBy('tanggal_layanan', 'desc')
                         ->orderBy('nomor_antrian', 'desc')
                         ->paginate(20);

        // Format tanggal dengan nama hari bahasa Indonesia
        $antrian->getCollection()->transform(function($item) {
            $item->tanggal_formatted = Carbon::parse($item->tanggal_layanan)->translatedFormat('l, d/m/Y');
            return $item;
        });

        return view('admin.dashboard', compact('antrian'));
    }

    public function statistik()
    {
        // Set locale Carbon ke Indonesia
        Carbon::setLocale('id');

        // Ambil statistik per bidang layanan
        $statistik = Antrian::select(
                'bidang_layanan',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "Selesai" THEN 1 ELSE 0 END) as selesai'),
                DB::raw('SUM(CASE WHEN status = "Batal" THEN 1 ELSE 0 END) as batal')
            )
            ->groupBy('bidang_layanan')
            ->orderBy('total', 'desc')
            ->get();

        return view('admin.statistik', compact('statistik'));
    }
}
