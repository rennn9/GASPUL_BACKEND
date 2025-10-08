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

    // 🔹 Method store untuk menerima POST dari Flutter
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama'       => 'required|string|max:255',
            'nip'        => 'required|string|max:50',
            'penjelasan' => 'required|string',
        ]);

        // Jika ada file, simpan
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('uploads', 'public');
            $validated['file'] = $path;
        }

        $pengaduan = PengaduanPelayanan::create($validated);

        // Response JSON untuk API
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'message' => 'Pengaduan pelayanan berhasil dikirim',
                'data' => $pengaduan
            ], 201);
        }

        // Response untuk web
        return redirect()->back()->with('success', 'Pengaduan pelayanan berhasil dikirim.');
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
