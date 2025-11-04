<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Konsultasi;
use App\Models\Antrian;
use Carbon\Carbon;
use PDF;
use Illuminate\Support\Str;
use App\Services\TiketService;


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


    // 🔹 Mengubah status konsultasi + sinkron ke tabel antrian
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:baru,proses,selesai,batal',
        ]);

        $konsultasi = Konsultasi::findOrFail($id);
        $oldStatus = $konsultasi->status;
        $newStatus = $request->status;

        $konsultasi->status = $newStatus;
        $konsultasi->save();

        // 🔹 Sinkronkan status ke tabel antrian (jika ada relasi)
        $antrian = Antrian::where('konsultasi_id', $konsultasi->id)->first();

        if ($antrian) {
            $mapStatus = [
                'baru' => 'Diproses',
                'proses' => 'Diproses',
                'selesai' => 'Selesai',
                'batal' => 'Batal',
            ];

            $antrian->status = $mapStatus[$newStatus] ?? 'Diproses';
            $antrian->save();
        }

        Log::info("Status konsultasi diubah", [
            'admin_id' => auth()->id(),
            'konsultasi_id' => $id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'timestamp' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status berhasil diupdate'
        ]);
    }


    // 🔹 Menghapus data konsultasi
    public function destroy($id)
    {
        $konsultasi = Konsultasi::findOrFail($id);

        if ($konsultasi->dokumen && \Storage::disk('public')->exists($konsultasi->dokumen)) {
            \Storage::disk('public')->delete($konsultasi->dokumen);
        }

        // 🔹 Hapus juga antrian terkait
        Antrian::where('konsultasi_id', $konsultasi->id)->delete();
        $konsultasi->delete();

        Log::info("Konsultasi dihapus", [
            'admin_id' => auth()->id(),
            'konsultasi_id' => $id,
            'timestamp' => now()
        ]);

        return redirect()
            ->route('admin.konsultasi')
            ->with('success', 'Data konsultasi berhasil dihapus');
    }


    // 🔹 API: Menyimpan data baru dari Android + generate antrian otomatis
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'no_hp'        => 'required|string|max:20',
            'email'        => 'nullable|email',
            'perihal'      => 'required|string|max:255',
            'isi_konsultasi' => 'required|string',
            'dokumen'      => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
        ]);

        DB::beginTransaction();

        try {
            // 🔹 Upload dokumen jika ada
            $filePath = null;
            if ($request->hasFile('dokumen')) {
                $file = $request->file('dokumen');
                $filename = time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
                $filePath = $file->storeAs('konsultasi', $filename, 'public');
            }

            // 🔹 Buat entri konsultasi
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

            // 🔹 Generate nomor antrian otomatis per tanggal
            $today = Carbon::today()->toDateString();
            $lastQueue = Antrian::whereDate('tanggal_daftar', $today)->max('nomor_antrian');
            $nextQueue = $lastQueue ? $lastQueue + 1 : 1;
            $nomorAntrian = sprintf('%03d', $nextQueue);

            // 🔹 Buat entri antrian terhubung
            $antrian = Antrian::create([
                'konsultasi_id' => $konsultasi->id,
                'nama' => $konsultasi->nama_lengkap,
                'no_hp' => $konsultasi->no_hp,
                'alamat' => 'Tidak ada alamat (form konsultasi)',
                'bidang_layanan' => 'Konsultasi',
                'layanan' => $konsultasi->perihal,
                'tanggal_daftar' => Carbon::now(),
                'keterangan' => 'Dari form konsultasi',
                'nomor_antrian' => $nomorAntrian,
                'qr_code_data' => 'KONSULTASI-' . $konsultasi->id,
                'status' => 'Diproses',
            ]);

// 🔹 Generate tiket PDF
$tiket = TiketService::generateTiket($antrian, true);

DB::commit();

Log::info("Konsultasi baru dibuat via API + Antrian otomatis", [
    'konsultasi_id' => $konsultasi->id,
    'antrian_id' => $antrian->id,
    'nama_lengkap' => $konsultasi->nama_lengkap,
    'no_hp' => $konsultasi->no_hp,
    'perihal' => $konsultasi->perihal,
    'nomor_antrian' => $antrian->nomor_antrian,
    'timestamp' => now()
]);

return response()->json([
    'success'       => true,
    'message'       => 'Konsultasi dan antrian berhasil dibuat',
    'nomor_antrian' => $antrian->nomor_antrian,
    'pdf_url'       => $tiket['pdf_url'],
], 200);



        } catch (\Throwable $th) {
            DB::rollBack();

            Log::error("Gagal menyimpan konsultasi", [
                'error' => $th->getMessage(),
                'timestamp' => now()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $th->getMessage(),
            ], 500);
        }
    }


    // 🔹 Download PDF daftar konsultasi
    public function downloadPdf(Request $request)
    {
        Log::info('🟢 downloadPdf() dipanggil oleh admin', [
            'admin_id' => auth()->id(),
            'query_string' => $request->query(),
            'timestamp' => now()
        ]);

        $status = $request->query('status', 'semua');
        $query = Konsultasi::query();

        if ($status !== 'semua') {
            $query->where('status', $status);
        }

        $konsultasis = $query->orderBy('tanggal_konsultasi', 'desc')->get();
        Carbon::setLocale('id');

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

            $fileName = "Daftar_Konsultasi_{$status}.pdf";
            $path = "konsultasi_pdf/{$fileName}";

            \Storage::disk('public')->put($path, $pdf->output());

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
