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
    /* ============================================================
     * 🔹 INDEX - Menampilkan daftar konsultasi untuk admin
     * ============================================================ */
    public function index(Request $request)
    {
        static $logged = false;
        Carbon::setLocale('id');

        $statusFilter = $request->query('status', 'semua');
        $query = Konsultasi::query();

        if ($statusFilter !== 'semua') {
            $query->where('status', $statusFilter);
        }

        $konsultasis = $query->orderBy('tanggal_layanan', 'desc')->paginate(20);

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


    /* ============================================================
     * 🔹 SHOW - Detail konsultasi
     * ============================================================ */
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


    /* ============================================================
     * 🔹 UPDATE STATUS - Sinkron ke tabel antrian
     * ============================================================ */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:baru,proses,selesai,batal',
        ]);

        $konsultasi = Konsultasi::findOrFail($id);
        $oldStatus = $konsultasi->status;
        $newStatus = $request->status;

        $konsultasi->update(['status' => $newStatus]);

        // Sinkron ke antrian
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


    /* ============================================================
     * 🔹 DESTROY - Hapus data konsultasi dan antrian terkait
     * ============================================================ */
    public function destroy($id)
    {
        $konsultasi = Konsultasi::findOrFail($id);

        // Hapus file dokumen jika ada
        if ($konsultasi->dokumen && \Storage::disk('public')->exists($konsultasi->dokumen)) {
            \Storage::disk('public')->delete($konsultasi->dokumen);
        }

        // Hapus antrian terkait
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


    /* ============================================================
     * 🔹 STORE (API) - Simpan data baru + generate antrian + tiket
     * ============================================================ */
public function store(Request $request)
{
    Log::info("🟢 [KONSULTASI] Request diterima", [
        'input' => $request->all(),
        'timestamp' => now()
    ]);

    try {
        $validated = $request->validate([
            'nama_lengkap'   => 'required|string|max:255',
            'no_hp_wa'       => 'required|string|max:20',
            'email'          => 'nullable|email',
            'perihal'        => 'required|string|max:255',
            'asal_instansi'  => 'nullable|string|max:255',
            'dokumen'        => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
        ]);

        DB::beginTransaction();

        // Upload dokumen
        $filePath = null;
        if ($request->hasFile('dokumen')) {
            $file = $request->file('dokumen');
            $filename = time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('konsultasi', $filename, 'public');
        }

        // Simpan ke tabel konsultasi
        $konsultasi = Konsultasi::create([
            'nama_lengkap'     => $validated['nama_lengkap'],
            'no_hp_wa'         => $validated['no_hp_wa'],
            'alamat'           => $request->alamat ?? null,
            'asal_instansi'    => $validated['asal_instansi'] ?? null,
            'email'            => $validated['email'] ?? null,
            'perihal'          => $validated['perihal'],
            'dokumen'          => $filePath,
            'status'           => 'baru',
            'tanggal_layanan'  => now(),
        ]);

        // Generate nomor antrian
        $today = Carbon::today()->toDateString();
        $lastQueue = Antrian::whereDate('tanggal_layanan', $today)->max('nomor_antrian');
        $nextQueue = $lastQueue ? $lastQueue + 1 : 1;
        $nomorAntrian = sprintf('%03d', $nextQueue);

        // Simpan ke tabel antrian
        $antrian = Antrian::create([
            'konsultasi_id'   => $konsultasi->id,
            'nama_lengkap'    => $konsultasi->nama_lengkap,
            'no_hp_wa'        => $konsultasi->no_hp_wa,
            'email'           => $konsultasi->email,
            'alamat'          => $request->alamat ?? 'Tidak ada alamat (form konsultasi)',
            'bidang_layanan'  => 'Konsultasi',
            'layanan'         => $konsultasi->perihal,
            'tanggal_layanan' => Carbon::now(),
            'keterangan'      => 'Dari form konsultasi',
            'nomor_antrian'   => $nomorAntrian,
            'qr_code_data'    => 'KONSULTASI-' . $konsultasi->id,
            'status'          => 'Diproses',
        ]);

        // Update tabel konsultasi
        $konsultasi->update([
            'nomor_antrian' => $nomorAntrian,
        ]);

        // Generate tiket PDF
        $tiket = TiketService::generateTiket($antrian, true);

        DB::commit();

        Log::info("✅ [KONSULTASI] Berhasil disimpan", [
            'konsultasi_id' => $konsultasi->id,
            'antrian_id' => $antrian->id,
            'nomor_antrian' => $antrian->nomor_antrian,
            'timestamp' => now()
        ]);

        return response()->json([
            'success'       => true,
            'message'       => 'Konsultasi dan antrian berhasil dibuat',
            'nomor_antrian' => $antrian->nomor_antrian,
            'pdf_url'       => $tiket['pdf_url'] ?? null,
        ], 200);

    } catch (\Illuminate\Validation\ValidationException $e) {
        Log::warning("⚠️ [KONSULTASI] Validasi gagal", [
            'errors' => $e->errors(),
            'input' => $request->all(),
            'timestamp' => now()
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Validasi gagal',
            'errors' => $e->errors(),
        ], 422);
    } catch (\Throwable $th) {
        DB::rollBack();

        Log::error("❌ [KONSULTASI] Gagal menyimpan", [
            'error' => $th->getMessage(),
            'trace' => $th->getTraceAsString(),
            'timestamp' => now()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Terjadi kesalahan: ' . $th->getMessage(),
        ], 500);
    }
}




    /* ============================================================
     * 🔹 DOWNLOAD PDF - Daftar konsultasi
     * ============================================================ */
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

        $konsultasis = $query->orderBy('tanggal_layanan', 'desc')->get();
        Carbon::setLocale('id');

        try {
            $pdf = PDF::loadView('admin.exports.konsultasi_pdf', [
                'konsultasis' => $konsultasis,
                'status' => $status
            ])->setPaper('a4', 'portrait');

            $tanggal = now()->format('d-m-Y');
            $fileName = "Daftar_Konsultasi_{$tanggal}.pdf";
            $path = "konsultasi_pdf/{$fileName}";

            \Storage::disk('public')->put($path, $pdf->output());

            Log::info("Admin mendownload PDF daftar konsultasi", [
                'admin_id' => auth()->id(),
                'status_filter' => $status,
                'jumlah_data' => $konsultasis->count(),
                'timestamp' => now()
            ]);

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
