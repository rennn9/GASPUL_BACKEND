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
            'sedang diproses'     => 'sedang diproses',
            'diterima'            => 'diterima',
            'ditolak'             => 'ditolak',
            'perlu perbaikan'     => 'perlu perbaikan',
            'selesai'             => 'selesai',
            'perbaikan selesai'   => 'perbaikan selesai',
            default               => strtolower(trim($status)),
        };
    }


    // =========================================================
    // GENERATE NOMOR REGISTRASI
    // =========================================================
    public function generateNomorRegistrasi(Request $request)
    {
        $request->validate([
            'bidang' => 'required',
            'layanan' => 'required',
        ]);

        $bidang = $request->bidang;
        $layanan = $request->layanan;

        $config = config('layanan');

        $urutanBidang = str_pad($config['bidang'][$bidang], 2, "0", STR_PAD_LEFT);
        $urutanLayanan = str_pad($config['layanan'][$bidang][$layanan], 2, "0", STR_PAD_LEFT);
        $tgl = now()->format('Ymd');

        $counter = LayananPublik::count() + 1;
        $XXX = str_pad($counter, max(3, strlen($counter)), '0', STR_PAD_LEFT);

        $no = "$tgl/$urutanBidang/$urutanLayanan/$XXX";

        Log::info('Generate Nomor Registrasi', [
            'bidang' => $bidang,
            'layanan' => $layanan,
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
public function index()
{
    $user = auth()->user();

    $query = LayananPublik::with('statusHistory.user')->latest();

    // Jika operator bidang, filter berdasarkan bidang miliknya
    if ($user->role === 'operator_bidang' && $user->bidang) {
        $query->where('bidang', $user->bidang);
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
        'file_surat.*'  => 'file|max:5000'
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
