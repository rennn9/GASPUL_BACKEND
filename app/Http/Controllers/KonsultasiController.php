<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Konsultasi;
use Carbon\Carbon;

class KonsultasiController extends Controller
{
    // Menampilkan daftar konsultasi untuk admin
    public function index(Request $request)
    {
        Carbon::setLocale('id');

        $query = Konsultasi::query();

        // Filter berdasarkan status jika ada
        if ($request->has('status') && $request->status != 'semua') {
            $query->where('status', $request->status);
        }

        // Pagination 20 data per halaman
        $konsultasis = $query->orderBy('tanggal_konsultasi', 'desc')
                            ->paginate(20);

        return view('admin.konsultasi.index', compact('konsultasis'));
    }

    // Menampilkan detail konsultasi
    public function show($id)
    {
        $konsultasi = Konsultasi::findOrFail($id);
        return view('admin.konsultasi.show', compact('konsultasi'));
    }

    // Mengubah status konsultasi
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:baru,proses,selesai,batal',
        ]);

        $konsultasi = Konsultasi::findOrFail($id);
        $konsultasi->status = $request->status;
        $konsultasi->save();

        return response()->json(['success' => true, 'message' => 'Status berhasil diupdate']);
    }

    // Menghapus data konsultasi
    public function destroy($id)
    {
        $konsultasi = Konsultasi::findOrFail($id);

        // Hapus file dokumen jika ada
        if ($konsultasi->dokumen && \Storage::disk('public')->exists($konsultasi->dokumen)) {
            \Storage::disk('public')->delete($konsultasi->dokumen);
        }

        $konsultasi->delete();

        return redirect()->route('admin.konsultasi')->with('success', 'Data konsultasi berhasil dihapus');
    }

    // API: Menyimpan data baru dari Android
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'no_hp' => 'required|string|max:20',
            'email' => 'nullable|email',
            'perihal' => 'required|string|max:255',
            'isi_konsultasi' => 'required|string',
            'dokumen' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
        ]);

        $filePath = null;
        if ($request->hasFile('dokumen')) {
            $filePath = $request->file('dokumen')->store('konsultasi', 'public');
        }

        Konsultasi::create([
            'nama_lengkap' => $validated['nama_lengkap'],
            'no_hp' => $validated['no_hp'],
            'email' => $validated['email'] ?? null,
            'perihal' => $validated['perihal'],
            'isi_konsultasi' => $validated['isi_konsultasi'],
            'dokumen' => $filePath,
            'status' => 'baru',
            'tanggal_konsultasi' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Konsultasi berhasil dikirim'
        ], 200);
    }
}
