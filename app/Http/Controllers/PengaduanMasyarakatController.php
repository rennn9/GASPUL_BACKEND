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

public function store(Request $request)
{
    // ✅ Validasi input
    $validated = $request->validate([
        'nama'           => 'required|string|max:255',
        'nip'            => 'required|string|max:50',
        'jenis_laporan'  => 'required|string',
        'penjelasan'     => 'required|string',
    ]);

    // ✅ Simpan ke database
    $pengaduan = PengaduanMasyarakat::create($validated);

    // ✅ Jika request dari Flutter (API), kirim JSON
    if ($request->expectsJson() || $request->wantsJson()) {
        return response()->json([
            'message' => 'Pengaduan masyarakat berhasil dikirim',
            'data' => $pengaduan
        ], 201);
    }

    // ✅ Jika dari web, redirect balik seperti biasa
    return redirect()->back()->with('success', 'Pengaduan masyarakat berhasil dikirim.');
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
