<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\LayananPublik;
use App\Models\StatusLayanan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class LayananPublikController extends Controller
{

    // =========================================================
    // HELPER NORMALISASI STATUS
    // =========================================================
    private function normalizeStatus($status)
    {
        if (!$status) return '';

        return match (strtolower(trim($status))) {
            'sedang diproses'           => 'sedang diproses',
            'menunggu verifikasi bidang' => 'menunggu verifikasi bidang',
            'diterima'                  => 'diterima',
            'ditolak'                   => 'ditolak',
            'perlu perbaikan'           => 'perlu perbaikan',
            'selesai'                   => 'selesai',
            'perbaikan selesai'         => 'perbaikan selesai',
            default                     => strtolower(trim($status)),
        };
    }


// =========================================================
// GENERATE NOMOR REGISTRASI (LOG DETAIL)
// =========================================================
public function generateNomorRegistrasi(Request $request)
{
    Log::info('[START] Generate Nomor Registrasi', [
        'request_data' => $request->all()
    ]);

    // ===============================
    // 1. VALIDASI INPUT
    // ===============================
    $request->validate([
        'bidang' => 'required',
        'layanan' => 'required',
    ]);

    $bidang = $request->bidang;
    $layanan = $request->layanan;

    Log::info('[VALIDASI] Input diterima', [
        'bidang' => $bidang,
        'layanan' => $layanan
    ]);

    // ===============================
    // 2. AMBIL KONFIGURASI
    // ===============================
    $config = config('layanan');
    Log::info('[CONFIG] Konfigurasi layanan diambil', [
        'config_bidang' => $config['bidang'] ?? null,
        'config_layanan' => $config['layanan'][$bidang] ?? null
    ]);

    // ===============================
    // 3. HITUNG URUTAN BIDANG & LAYANAN
    // ===============================
    $urutanBidang = str_pad($config['bidang'][$bidang] ?? 0, 2, "0", STR_PAD_LEFT);
    $urutanLayanan = str_pad($config['layanan'][$bidang][$layanan] ?? 0, 2, "0", STR_PAD_LEFT);

    Log::info('[URUTAN] Urutan bidang & layanan dihitung', [
        'urutan_bidang' => $urutanBidang,
        'urutan_layanan' => $urutanLayanan
    ]);

    // ===============================
    // 4. TANGGAL DAN COUNTER
    // ===============================
    $tgl = now()->format('Ymd');
    $counter = LayananPublik::count() + 1;
    $XXX = str_pad($counter, max(3, strlen($counter)), '0', STR_PAD_LEFT);

    Log::info('[COUNTER] Counter & format nomor', [
        'tanggal' => $tgl,
        'counter' => $counter,
        'XXX' => $XXX
    ]);

    // ===============================
    // 5. GENERATE NOMOR REGISTRASI
    // ===============================
    $no = "$tgl/$urutanBidang/$urutanLayanan/$XXX";

    Log::info('[END] Nomor Registrasi berhasil dibuat', [
        'no_registrasi' => $no
    ]);

    return response()->json([
        'success' => true,
        'no_registrasi' => $no
    ]);
}



    // =========================================================
    // STORE DATA (PENGAJUAN PUBLIK)
    // =========================================================
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nik' => 'required',
            'nama' => 'required|string|max:255',
            'telepon' => 'required|string|max:20',
            'email' => 'nullable|email',
            'bidang' => 'required',
            'layanan' => 'required',
            'no_registrasi' => 'required',
            'berkas' => 'nullable|array',
            'berkas.*' => 'file',
        ]);

        $filePaths = [];
        if ($request->hasFile('berkas')) {
            foreach ($request->file('berkas') as $file) {
                $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                $uniqueName = $filename . '_' . time() . '.' . $extension;

                $path = $file->storeAs('berkas-layanan', $uniqueName, 'public');
                $filePaths[] = $path;
            }
        }

        $layanan = LayananPublik::create([
            'nik' => $request->nik,
            'nama' => $request->nama,
            'email' => $request->email,
            'telepon' => $request->telepon,
            'no_registrasi' => $request->no_registrasi,
            'bidang' => $request->bidang,
            'layanan' => $request->layanan,
            'berkas' => json_encode($filePaths),
        ]);

        StatusLayanan::create([
            'layanan_id' => $layanan->id,
            'user_id' => null,
            'status' => 'Sedang Diproses',
            'keterangan' => null,
            'file_surat' => null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $layanan,
        ]);
    }


    // =========================================================
    // ADMIN VIEW LIST
    // =========================================================
public function index(Request $request)
{
    $user = auth()->user();

    $query = LayananPublik::with('statusHistory.user')->latest();

    // Jika operator bidang, filter berdasarkan bidang miliknya
    if ($user->role === 'operator_bidang' && $user->bidang) {
        $query->where('bidang', $user->bidang);
    }

    // Filter berdasarkan status
    if ($request->has('status') && $request->status != '') {
        $filterStatus = strtolower(trim($request->status));

        $query->whereHas('statusHistory', function($q) use ($filterStatus) {
            $q->whereRaw('LOWER(status) = ?', [$filterStatus])
              ->whereIn('id', function($subQuery) {
                  $subQuery->selectRaw('MAX(id)')
                           ->from('status_layanan')
                           ->groupBy('layanan_id');
              });
        });
    }

    $data = $query->paginate(20);

    return view('admin.layanan.index', [
        'title' => 'Layanan Publik',
        'data' => $data
    ]);
}


    // =========================================================
    // ADMIN DETAIL
    // =========================================================
    public function show($id)
    {
        $item = LayananPublik::with('statusHistory.user')->findOrFail($id);

        return view('admin.layanan.show', [
            'title' => 'Detail Layanan Publik',
            'item' => $item
        ]);
    }


// =========================================================
// ADD STATUS (FULL DEBUG VERSION)
// =========================================================
public function addStatus(Request $request, $id)
{
    $layanan = LayananPublik::findOrFail($id);
    $user = auth()->user();

    // ===== LOG MULAI =====
    Log::info("===== ADD STATUS START =====", [
        'layanan_id'        => $layanan->id,
        'user_id'           => $user->id,
        'role'              => $user->role,
        'input_status_raw'  => $request->input('status'),
        'input_keterangan'  => $request->input('keterangan'),
    ]);

    // Ambil status terakhir
    $lastEntry = StatusLayanan::where('layanan_id', $layanan->id)
        ->orderBy('id', 'DESC')
        ->first();

    $lastStatusRaw = $lastEntry?->status;
    $lastNormalized = $lastStatusRaw ? strtolower(trim($lastStatusRaw)) : null;

    $reqRaw = $request->input('status');
    $reqNormalized = strtolower(trim($reqRaw));

    Log::info("StatusCheck", [
        'last_entry_id' => $lastEntry?->id,
        'last_status_raw' => $lastStatusRaw,
        'last_normalized' => $lastNormalized,
        'requested_status_raw' => $reqRaw,
        'requested_status_normalized' => $reqNormalized
    ]);

    // =====================================================
    // RULE UMUM: Tidak boleh update jika sudah ditolak
    // =====================================================
    if ($lastNormalized === 'ditolak') {
        Log::warning("Blocked_DitolakTidakBolehDiubah", [
            'layanan_id' => $layanan->id,
            'last_status' => $lastNormalized,
            'requested_status' => $reqNormalized,
            'user_role' => $user->role
        ]);
        return back()->with('error', 'Pengajuan sudah ditolak dan tidak dapat diubah lagi.');
    }

    // =====================================================
    // RULE OPERATOR BIDANG
    // =====================================================
    if ($user->role === 'operator_bidang') {
        Log::info("OperatorBidangCheck", [
            'last_status' => $lastNormalized,
            'requested_status' => $reqNormalized
        ]);

        // Tidak boleh memberi status jika belum "Menunggu Verifikasi Bidang"
        if ($lastNormalized !== 'menunggu verifikasi bidang') {
            Log::warning("OperatorBidangBlocked_BelumDikirim", [
                'last' => $lastNormalized
            ]);
            return back()->with('error', 'Entri harus dikirim untuk diverifikasi oleh operator terlebih dahulu.');
        }

        // Hanya status final yang tidak bisa diubah
        $finalStatus = ['selesai', 'ditolak'];

        if (in_array($lastNormalized, $finalStatus)) {
            Log::warning("OperatorBidangBlocked_FinalStatus", [
                'last' => $lastNormalized
            ]);
            return back()->with('error', 'Status sudah final dan tidak dapat diubah lagi.');
        }

        $allowed = ['diterima', 'ditolak', 'perlu perbaikan'];
        if (!in_array($reqNormalized, $allowed)) {
            Log::warning("OperatorBidangBlocked_InvalidStatus", [
                'requested' => $reqNormalized,
                'allowed' => $allowed
            ]);
            return back()->with('error', 'Operator bidang hanya boleh: Diterima, Ditolak, Perlu Perbaikan.');
        }

        Log::info("OperatorBidangCheck_Passed");
    }

    // =====================================================
    // RULE OPERATOR BIASA
    // =====================================================
    $filePerbaikanPath = null;

    if ($user->role === 'operator') {
        Log::info("OperatorCheck", [
            'last_status' => $lastNormalized,
            'requested_status' => $reqNormalized
        ]);

        // Operator tidak boleh memberi status jika masih "sedang diproses"
        if ($lastNormalized === 'sedang diproses') {
            Log::warning("OperatorBlocked_MasihSedangDiproses", [
                'last' => $lastNormalized
            ]);
            return back()->with('error', 'Silakan kirim untuk diverifikasi terlebih dahulu.');
        }

        // Operator tidak boleh memberi status jika masih "menunggu verifikasi bidang"
        if ($lastNormalized === 'menunggu verifikasi bidang') {
            Log::warning("OperatorBlocked_MenungguVerifikasiBidang", [
                'last' => $lastNormalized
            ]);
            return back()->with('error', 'Entri sedang menunggu verifikasi dari bidang.');
        }

        $allowedOp = ['selesai', 'perbaikan selesai'];
        if (!in_array($reqNormalized, $allowedOp)) {
            Log::warning("OperatorBlocked_InvalidStatus", [
                'requested' => $reqNormalized,
                'allowed' => $allowedOp
            ]);
            return back()->with('error', 'Operator hanya boleh: Selesai atau Perbaikan Selesai.');
        }

        if ($reqNormalized === 'selesai' && $lastNormalized !== 'diterima') {
            Log::warning("OperatorBlocked_SelesaiNotAllowed", [
                'requested' => $reqNormalized,
                'last' => $lastNormalized
            ]);
            return back()->with('error', 'Status Selesai hanya setelah Diterima.');
        }

        if ($reqNormalized === 'perbaikan selesai' && $lastNormalized !== 'perlu perbaikan') {
            Log::warning("OperatorBlocked_PerbaikanSelesaiNotAllowed", [
                'requested' => $reqNormalized,
                'last' => $lastNormalized
            ]);
            return back()->with('error', 'Perbaikan Selesai hanya setelah Perlu Perbaikan.');
        }

// ===================== FILE PERBAIKAN UPLOAD LOGGING =====================
if ($reqNormalized === 'perbaikan selesai') {
    Log::info("Operator_PerbaikanSelesai_FileUploadStart", [
        'reqNormalized' => $reqNormalized,
        'hasFile' => $request->hasFile('file_perbaikan'),
        'allKeys' => $request->all()
    ]);

    // Validasi file
    Log::info("Operator_PerbaikanSelesai_ValidationStart");

    try {
        $request->validate([
            'file_perbaikan' => 'required|file|mimes:pdf|max:5000'
        ]);
        Log::info("Operator_PerbaikanSelesai_ValidationSuccess");
    } catch (\Exception $e) {
        Log::error("Operator_PerbaikanSelesai_ValidationFailed", [
            'error' => $e->getMessage()
        ]);
        throw $e;
    }

    // Cek apakah file ada
    if (!$request->hasFile('file_perbaikan')) {
        Log::error("Operator_PerbaikanSelesai_FileMissingAfterValidation", [
            'input_keys' => array_keys($request->all())
        ]);
        return back()->with('error', 'File perbaikan PDF wajib diupload.');
    }

    $file = $request->file('file_perbaikan');

    Log::info("Operator_PerbaikanSelesai_FileDetected", [
        'original_name' => $file->getClientOriginalName(),
        'mime' => $file->getMimeType(),
        'size' => $file->getSize(),
        'temp_path' => $file->getRealPath()
    ]);

    // Simpan file
    $unique = 'perbaikan_' . time() . '_' . $file->getClientOriginalName();

    Log::info("Operator_PerbaikanSelesai_StoreAttempt", [
        'unique_name' => $unique,
        'disk' => 'public',
        'target_folder' => 'perbaikan-layanan'
    ]);

    try {
        $filePerbaikanPath = $file->storeAs('perbaikan-layanan', $unique, 'public');

        Log::info("Operator_PerbaikanSelesai_FileStored", [
            'stored_name' => $unique,
            'stored_path' => $filePerbaikanPath,
            'full_url' => asset('storage/'.$filePerbaikanPath)
        ]);
    } catch (\Exception $e) {
        Log::error("Operator_PerbaikanSelesai_FileStoreFailed", [
            'error' => $e->getMessage()
        ]);
        throw $e;
    }

    // Pastikan variabel terisi
    if (empty($filePerbaikanPath)) {
        Log::error("Operator_PerbaikanSelesai_FilePathEmpty", [
            'filePerbaikanPath' => $filePerbaikanPath
        ]);
    } else {
        Log::info("Operator_PerbaikanSelesai_FilePathOK", [
            'filePerbaikanPath' => $filePerbaikanPath
        ]);
    }
}}
// ===================== END FILE PERBAIKAN UPLOAD LOGGING =====================


    // =====================================================
    // VALIDASI GLOBAL
    // =====================================================
    Log::info("ValidationStart");
    $validated = $request->validate([
        'status'        => 'required|string',
        'keterangan'    => 'nullable|string',
        'file_surat.*'  => 'file|mimes:pdf|max:5000'
    ]);
    Log::info("ValidationSuccess");

    // Upload surat balasan jika ada
    $files = [];
    if ($request->hasFile('file_surat')) {
        foreach ($request->file('file_surat') as $f) {
            $name = uniqid() . '_' . $f->getClientOriginalName();
            $storedPath = $f->storeAs('surat-balasan', $name, 'public');
            $files[] = $storedPath;

            Log::info("FileSurat_Uploaded", [
                'original_name' => $f->getClientOriginalName(),
                'stored_name' => $name,
                'stored_path' => $storedPath
            ]);
        }
    }

    // =====================================================
    // SIMPAN STATUS
    // =====================================================
    $saveStatus = ucwords($reqNormalized);

    $save = StatusLayanan::create([
        'layanan_id'     => $layanan->id,
        'user_id'        => $user->id,
        'status'         => $saveStatus,
        'keterangan'     => $validated['keterangan'] ?? null,
        'file_surat'     => $files ? json_encode($files) : null,
        'file_perbaikan' => $filePerbaikanPath,
    ]);

    Log::info("StatusSavedSuccess", [
        'saved_id'       => $save->id,
        'new_status'     => $saveStatus,
        'file_perbaikan' => $filePerbaikanPath,
        'file_surat_count'=> count($files)
    ]);

    Log::info("===== ADD STATUS END =====");

    return back()->with('success', 'Status berhasil ditambahkan.');
}


    // =========================================================
    // KIRIM ENTRI UNTUK DIVERIFIKASI (OPERATOR)
    // =========================================================
    public function kirimVerifikasi($id)
    {
        $layanan = LayananPublik::findOrFail($id);
        $user = auth()->user();

        Log::info("===== KIRIM VERIFIKASI START =====", [
            'layanan_id' => $layanan->id,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'role' => $user->role
        ]);

        // Validasi role
        if ($user->role !== 'operator') {
            Log::warning("Akses ditolak: bukan operator", ['role' => $user->role]);
            return back()->with('error', 'Hanya operator yang dapat mengirim untuk diverifikasi.');
        }

        // Ambil status terakhir
        $lastStatus = StatusLayanan::where('layanan_id', $layanan->id)
            ->orderBy('id', 'DESC')
            ->first();

        $lastNormalized = strtolower(trim($lastStatus->status));

        Log::info("Status terakhir", [
            'status_id' => $lastStatus->id,
            'status' => $lastNormalized
        ]);

        // Validasi: hanya bisa kirim jika status "sedang diproses"
        if ($lastNormalized !== 'sedang diproses') {
            Log::warning("Status tidak valid untuk pengiriman", [
                'current_status' => $lastNormalized
            ]);
            return back()->with('error', 'Hanya entri dengan status "Sedang Diproses" yang bisa dikirim untuk diverifikasi.');
        }

        // Cek apakah sudah pernah dikirim (ada status "menunggu verifikasi bidang")
        $sudahDikirim = StatusLayanan::where('layanan_id', $layanan->id)
            ->whereRaw('LOWER(status) = ?', ['menunggu verifikasi bidang'])
            ->exists();

        if ($sudahDikirim) {
            Log::warning("Entri sudah pernah dikirim", ['layanan_id' => $layanan->id]);
            return back()->with('error', 'Entri ini sudah dikirim untuk diverifikasi.');
        }

        // Buat record status baru
        $newStatus = StatusLayanan::create([
            'layanan_id' => $layanan->id,
            'user_id' => $user->id,
            'status' => 'Menunggu Verifikasi Bidang',
            'keterangan' => "Dikirim untuk diverifikasi oleh {$user->name}",
            'file_surat' => null,
            'file_perbaikan' => null,
            'operator_nama' => $user->name,
            'operator_no_hp' => $user->no_hp,
        ]);

        Log::info("Status baru berhasil dibuat", [
            'new_status_id' => $newStatus->id,
            'status' => $newStatus->status,
            'operator_name' => $user->name
        ]);

        Log::info("===== KIRIM VERIFIKASI END =====");

        return back()->with('success', 'Entri berhasil dikirim untuk diverifikasi oleh bidang.');
    }

    // =========================================================
    // DOWNLOAD BUKTI TERIMA BERKAS PERMOHONAN PTSP (DOCX)
    // =========================================================
    public function downloadBuktiTerima($id)
    {
        try {
            $user = auth()->user();

            Log::info("===== DOWNLOAD BUKTI TERIMA START =====", [
                'layanan_id' => $id,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'role' => $user->role
            ]);

            // 1. Validasi role: hanya superadmin, admin, operator
            if (!in_array($user->role, ['superadmin', 'admin', 'operator'])) {
                Log::warning("Akses ditolak: role tidak diizinkan", [
                    'role' => $user->role
                ]);
                return back()->with('error', 'Anda tidak memiliki akses untuk download dokumen ini.');
            }

            // 2. Ambil data layanan dengan relasi
            $layanan = LayananPublik::with(['statusHistory' => function($query) {
                $query->with('user');
            }])->findOrFail($id);

            Log::info("Data layanan ditemukan", [
                'no_registrasi' => $layanan->no_registrasi,
                'nama' => $layanan->nama
            ]);

            // 3. Validasi: cek apakah sudah pernah ada status "Menunggu Verifikasi Bidang"
            $hasVerifikasiStatus = $layanan->statusHistory->contains(function($st) {
                return strtolower(trim($st->status)) === 'menunggu verifikasi bidang';
            });

            if (!$hasVerifikasiStatus) {
                Log::warning("Status 'Menunggu Verifikasi Bidang' belum ada", [
                    'layanan_id' => $id
                ]);
                return back()->with('error', 'Dokumen bukti terima hanya tersedia setelah pengajuan dikirim untuk diverifikasi.');
            }

            // 4. Generate dokumen menggunakan DocumentService
            Log::info("Memanggil DocumentService untuk generate dokumen");

            $result = \App\Services\DocumentService::generateBuktiTerima($layanan);

            Log::info("Dokumen berhasil digenerate", [
                'file_name' => $result['file_name'],
                'file_path' => $result['file_path']
            ]);

            // 5. Return download response
            Log::info("===== DOWNLOAD BUKTI TERIMA END (SUCCESS) =====");

            return response()->download(
                $result['file_path'],
                $result['file_name'],
                [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ]
            )->deleteFileAfterSend(true); // Auto cleanup setelah download

        } catch (\Throwable $e) {
            Log::error("===== DOWNLOAD BUKTI TERIMA FAILED =====", [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Gagal menggenerate dokumen bukti terima. Silakan coba lagi atau hubungi administrator.');
        }
    }


    // =========================================================
    // ADMIN DOWNLOAD FILE
    // =========================================================
    public function downloadFile($filename)
    {
        $path = 'berkas-layanan/' . $filename;

        if (!Storage::disk('public')->exists($path)) {
            abort(404, 'File tidak ditemukan');
        }

        return Storage::disk('public')->download($path);
    }


    // =========================================================
    // DELETE RECORD
    // =========================================================
    public function destroy($id)
    {
        $item = LayananPublik::findOrFail($id);

        if ($item->berkas) {
            foreach (json_decode($item->berkas, true) as $file) {
                Storage::disk('public')->delete($file);
            }
        }

        $item->delete();

        return redirect()->back()->with('success', 'Data berhasil dihapus');
    }
}
