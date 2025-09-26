<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PengaduanPelayanan;
use PDF;

class PengaduanPelayananController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'all');
        $query = PengaduanPelayanan::query();

        // Filter waktu
        if ($filter === 'week') {
            $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($filter === 'month') {
            $query->whereMonth('created_at', now()->month);
        } elseif ($filter === 'year') {
            $query->whereYear('created_at', now()->year);
        } elseif ($filter === 'last_year') {
            $query->whereYear('created_at', now()->subYear()->year);
        }

        $data = $query->orderBy('created_at', 'desc')->get();

        return view('admin.pelayanan', compact('data', 'filter'));
    }

    public function exportPdf(Request $request)
    {
        $filter = $request->get('filter', 'all');
        $query = PengaduanPelayanan::query();

        if ($filter === 'week') {
            $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($filter === 'month') {
            $query->whereMonth('created_at', now()->month);
        } elseif ($filter === 'year') {
            $query->whereYear('created_at', now()->year);
        } elseif ($filter === 'last_year') {
            $query->whereYear('created_at', now()->subYear()->year);
        }

        $data = $query->orderBy('created_at', 'desc')->get();

        $pdf = PDF::loadView('admin.exports.pelayanan_pdf', compact('data'));
        return $pdf->download('pengaduan_pelayanan.pdf');
    }
}
