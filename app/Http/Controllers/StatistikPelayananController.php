<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Antrian;
use Illuminate\Support\Facades\DB;

class StatistikPelayananController extends Controller
{
    public function index()
    {
        $statistik = Antrian::select(
                'bidang_layanan',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "Selesai" THEN 1 ELSE 0 END) as selesai'),
                DB::raw('SUM(CASE WHEN status = "Batal" THEN 1 ELSE 0 END) as batal')
            )
            ->groupBy('bidang_layanan')
            ->get();

        return response()->json($statistik);
    }
}
