<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LayananPublik;

class StatusLayananController extends Controller
{
    public function cekStatus(Request $request)
    {
        \Log::info("=== [CEK STATUS] REQUEST MASUK ===", [
            'payload' => $request->all(),
            'timestamp' => now()->toDateTimeString()
        ]);

        // ----------------------------------------------------------
        // 0. Validasi input
        // ----------------------------------------------------------
        \Log::info("[VALIDASI] Memvalidasi input...");

        $request->validate([
            'no_registrasi' => 'required|string'
        ]);

        \Log::info("[VALIDASI] Done. Input valid.", [
            'no_registrasi' => $request->no_registrasi
        ]);


        // ----------------------------------------------------------
        // 1. Ambil data layanan + relasi
        // ----------------------------------------------------------
        \Log::info("[DB QUERY] Mencari layanan berdasarkan nomor registrasi...");

        $layanan = LayananPublik::where('no_registrasi', $request->no_registrasi)
            ->with(['statusHistory', 'lastStatus'])
            ->first();

        if (!$layanan) {
            \Log::warning("[DB RESULT] Tidak ditemukan.", [
                'no_registrasi' => $request->no_registrasi
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Nomor registrasi tidak ditemukan'
            ], 404);
        }

        \Log::info("[DB RESULT] Layanan ditemukan.", [
            'layanan_id' => $layanan->id,
            'berkas_raw' => $layanan->berkas,
            'last_status_id' => $layanan->lastStatus->id ?? null,
            'jumlah_history' => $layanan->statusHistory->count()
        ]);


        // ==========================================================
        // 2. NORMALISASI BERKAS UTAMA
        // ==========================================================
        \Log::info("[BERKAS] Normalisasi berkas utama dimulai...");

        $rawBerkas = $layanan->berkas;
        $fileName = null;

        if (is_string($rawBerkas) && str_starts_with($rawBerkas, '[')) {
            \Log::info("[BERKAS] Berkas terdeteksi sebagai JSON array, decoding...");

            $decoded = json_decode($rawBerkas, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                \Log::error("[BERKAS] ERROR JSON decode.", [
                    'error' => json_last_error_msg(),
                    'raw' => $rawBerkas
                ]);
            }

            \Log::info("[BERKAS] Hasil decoding:", [
                'decoded' => $decoded
            ]);

            if (is_array($decoded) && count($decoded) > 0) {
                $fileName = basename($decoded[0]);

                \Log::info("[BERKAS] Mengambil file pertama dari array:", [
                    'fileName' => $fileName
                ]);
            } else {
                \Log::warning("[BERKAS] Decoded JSON tetapi array kosong.");
            }
        } else {
            \Log::info("[BERKAS] Berkas berupa string biasa, ambil basename.");

            $fileName = basename($rawBerkas);

            \Log::info("[BERKAS] FileName hasil basename:", [
                'fileName' => $fileName
            ]);
        }

        $berkasUrl = $fileName
            ? asset("storage/berkas-layanan/" . $fileName)
            : null;

        \Log::info("[BERKAS] Final URL berkas:", [
            'berkas_url' => $berkasUrl
        ]);


/* ==========================================================
   3. NORMALISASI SURAT BALASAN (dari status terakhir YANG ADA file_surat)
========================================================== */
\Log::info("[SURAT BALASAN] Mencari status terakhir yang punya file_surat...");

$lastStatusWithSurat = $layanan->statusHistory()
    ->whereNotNull('file_surat')
    ->orderByDesc('id')
    ->first();

if (!$lastStatusWithSurat) {
    \Log::warning("[SURAT BALASAN] Tidak ada status yang memiliki file_surat.");
    $suratBalasanUrl = null;
} else {
    \Log::info("[SURAT BALASAN] Status ditemukan.", [
        'status_id' => $lastStatusWithSurat->id,
        'raw_file_surat' => $lastStatusWithSurat->file_surat
    ]);

    $suratArr = json_decode($lastStatusWithSurat->file_surat, true);

    if (is_array($suratArr) && count($suratArr) > 0) {
        $suratFile = basename($suratArr[0]);
        $suratBalasanUrl = asset("storage/surat-balasan/" . $suratFile);

        \Log::info("[SURAT BALASAN] URL surat balasan:", [
            'surat_balasan_url' => $suratBalasanUrl
        ]);
    } else {
        \Log::warning("[SURAT BALASAN] file_surat ada tapi tidak valid.");
        $suratBalasanUrl = null;
    }
}



        // ==========================================================
        // 4. RESPONSE AKHIR
        // ==========================================================
        \Log::info("[RESPONSE] Mengirim response JSON final...", [
            'berkas_url' => $berkasUrl,
            'surat_balasan_url' => $suratBalasanUrl,
            'success' => true,
            'layanan_id' => $layanan->id
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'info' => $layanan,
                'status_terakhir' => $layanan->lastStatus,
                'status_history' => $layanan->statusHistory,
                'berkas_url' => $berkasUrl,
                'surat_balasan_url' => $suratBalasanUrl,
            ]
        ]);
    }
}
