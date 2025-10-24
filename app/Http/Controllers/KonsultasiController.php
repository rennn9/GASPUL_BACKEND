<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\Konsultasi;
use Carbon\Carbon;
use PDF;

class KonsultasiController extends Controller
{
    // 🔹 Menampilkan daftar konsultasi untuk admin
    public function index(Request $request)
    {
        static $logged = false;
        Carbon::setLocale('id');

        $query = Konsultasi::query();
        $statusFilter = $request->query('status', 'semua');

        if ($statusFilter != 'semua') {
            $query->where('status', $statusFilter);
        }

        $konsultasis = $query->orderBy('tanggal_konsultasi', 'desc')->paginate(20);

        if (!$logged) {
            Log::info("Admin melihat daftar konsultasi", [
                'admin_id' => auth()->id(),
                'status_filter' => $statusFilter,
                'data_count' => $konsultasis->count(),
                'timestamp' => now()
            ]);
            $logged = true;
        }

        return view('admin.konsultasi.index', compact('konsultasis'));
    }

    // 🔹 Menampilkan detail konsultasi
    public function show($id)
    {
        $konsultasi = Konsultasi::findOrFail($id);

        Log::info("Admin melihat detail konsultasi", [
            'admin_id' => auth()->id(),
            'konsultasi_id' => $id,
            'timestamp' => now()
        ]);

        return view('admin.konsultasi.show', compact('konsultasi'));
    }

    // 🔹 Mengubah status konsultasi
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:baru,proses,selesai,batal',
        ]);

        $konsultasi = Konsultasi::findOrFail($id);
        $oldStatus = $konsultasi->status;
        $konsultasi->status = $request->status;
        $konsultasi->save();

        Log::info("Status konsultasi diubah", [
            'admin_id' => auth()->id(),
            'konsultasi_id' => $id,
            'old_status' => $oldStatus,
            'new_status' => $request->status,
            'timestamp' => now()
        ]);

        return response()->json(['success' => true, 'message' => 'Status berhasil diupdate']);
    }

    // 🔹 Menghapus data konsultasi
    public function destroy($id)
    {
        $konsultasi = Konsultasi::findOrFail($id);

        if ($konsultasi->dokumen && \Storage::disk('public')->exists($konsultasi->dokumen)) {
            \Storage::disk('public')->delete($konsultasi->dokumen);
        }

        $konsultasi->delete();

        Log::info("Konsultasi dihapus", [
            'admin_id' => auth()->id(),
            'konsultasi_id' => $id,
            'timestamp' => now()
        ]);

        return redirect()->route('admin.konsultasi')->with('success', 'Data konsultasi berhasil dihapus');
    }

    // 🔹 API: Menyimpan data baru dari Android
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

        $konsultasi = Konsultasi::create([
            'nama_lengkap' => $validated['nama_lengkap'],
            'no_hp' => $validated['no_hp'],
            'email' => $validated['email'] ?? null,
            'perihal' => $validated['perihal'],
            'isi_konsultasi' => $validated['isi_konsultasi'],
            'dokumen' => $filePath,
            'status' => 'baru',
            'tanggal_konsultasi' => now(),
        ]);

        Log::info("Konsultasi baru dibuat via API", [
            'konsultasi_id' => $konsultasi->id,
            'nama_lengkap' => $konsultasi->nama_lengkap,
            'no_hp' => $konsultasi->no_hp,
            'email' => $konsultasi->email,
            'perihal' => $konsultasi->perihal,
            'timestamp' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Konsultasi berhasil dikirim'
        ], 200);
    }

    // 🔹 Download PDF dengan log lengkap
    public function downloadPdf(Request $request)
    {
        Log::info('🟢 downloadPdf() dipanggil oleh admin', [
            'admin_id' => auth()->id(),
            'query_string' => $request->query(),
            'timestamp' => now()
        ]);
        $status = $request->query('status', 'semua');
        $query = Konsultasi::query();
        if ($status !== 'semua') $query->where('status', $status);
        $konsultasis = $query->orderBy('tanggal_konsultasi', 'desc')->get();

        Carbon::setLocale('id');

        // ✅ Tambahkan logging di sini
        Log::info("Admin mendownload PDF daftar konsultasi", [
            'admin_id' => auth()->id(),
            'status_filter' => $status,
            'jumlah_data' => $konsultasis->count(),
            'timestamp' => now()
        ]);

        try {
            $pdf = PDF::loadView('admin.exports.konsultasi_pdf', [
                'konsultasis' => $konsultasis,
                'status' => $status
            ])->setPaper('a4', 'portrait');

            // simpan ke storage/public
            $fileName = "Daftar_Konsultasi_{$status}.pdf";
            $path = "konsultasi_pdf/{$fileName}";
            \Storage::disk('public')->put($path, $pdf->output());

            // kembalikan URL publik
            return response()->json([
                'success' => true,
                'url' => asset('storage/' . $path)
            ]);

        } catch (\Exception $e) {
            Log::error('Gagal generate PDF', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id(),
                'timestamp' => now()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal generate PDF'
            ]);
        }
    }
}
