<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Konsultasi;
use Illuminate\Support\Facades\DB;

class StatistikKonsultasiController extends Controller
{
    public function index()
    {
        $statistik = Konsultasi::select(
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "selesai" THEN 1 ELSE 0 END) as selesai'),
                DB::raw('SUM(CASE WHEN status = "batal" THEN 1 ELSE 0 END) as batal')
            )
            ->first(); // ambil satu baris saja, karena total keseluruhan

        return view('admin.statistik.konsultasi', compact('statistik'));
    }
}
