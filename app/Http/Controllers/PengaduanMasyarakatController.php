<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PengaduanMasyarakat;
use PDF;

class PengaduanMasyarakatController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'all');
        $query = PengaduanMasyarakat::query();

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

        return view('admin.masyarakat', compact('data', 'filter'));
    }

    public function exportPdf(Request $request)
    {
        $filter = $request->get('filter', 'all');
        $query = PengaduanMasyarakat::query();

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

        $pdf = PDF::loadView('admin.exports.masyarakat_pdf', compact('data'));
        return $pdf->download('pengaduan_masyarakat.pdf');
    }
}
