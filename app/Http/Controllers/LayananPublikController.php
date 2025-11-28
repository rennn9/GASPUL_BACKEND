<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\LayananPublik;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class LayananPublikController extends Controller
{
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
    // STORE DATA (API for Next.js)
    // =========================================================
    public function store(Request $request)
    {
        Log::info("========== START STORE LAYANAN PUBLIK ==========");
        Log::info("Request metadata", [
            'method' => $request->method(),
            'content_type' => $request->header('Content-Type'),
            'has_files' => $request->hasFile('berkas'),
            'raw_files' => $request->file('berkas'),
        ]);

        Log::info("Full Request Input", $request->all());

        try {

            // VALIDATION
            $validated = $request->validate([
                'nik' => 'required',
                'bidang' => 'required',
                'layanan' => 'required',
                'no_registrasi' => 'required',
                'berkas' => 'nullable|array',
                'berkas.*' => 'file',
            ]);

            Log::info("Validation SUCCESS", $validated);

// UPLOAD FILES
$filePaths = [];

if ($request->hasFile('berkas')) {
    foreach ($request->file('berkas') as $file) {
        // Ambil nama asli
        $originalName = $file->getClientOriginalName();
        $filename = pathinfo($originalName, PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();

        // Buat nama unik agar tidak tertimpa
        $uniqueName = $filename . '_' . time() . '.' . $extension;

        // Simpan file di storage/public/berkas-layanan
        $path = $file->storeAs('berkas-layanan', $uniqueName, 'public');

        $filePaths[] = $path;

        Log::info("File uploaded", [
            'original_name' => $originalName,
            'stored_path' => $path
        ]);
    }
}


            // INSERT DATABASE
            $layanan = LayananPublik::create([
                'nik' => $request->nik,
                'no_registrasi' => $request->no_registrasi,
                'bidang' => $request->bidang,
                'layanan' => $request->layanan,
                'berkas' => json_encode($filePaths),
            ]);

            Log::info("DATABASE INSERT SUCCESS", [
                "id" => $layanan->id,
                "nik" => $layanan->nik,
                "no_registrasi" => $layanan->no_registrasi,
                "stored_files" => $filePaths
            ]);

            Log::info("========== END STORE LAYANAN PUBLIK ==========");

            return response()->json([
                'success' => true,
                'data' => $layanan,
            ]);

        } catch (\Exception $e) {

            Log::error("❌ STORE FAILED WITH EXCEPTION", [
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'request_input' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan di server.',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    // =========================================================
    // ADMIN VIEW: LIST DATA
    // =========================================================
    public function index()
    {
        $data = LayananPublik::latest()->paginate(20);

        return view('admin.layanan.index', [
            'title' => 'Layanan Publik',
            'data' => $data
        ]);
    }



    // =========================================================
    // ADMIN VIEW: DETAIL
    // =========================================================
    public function show($id)
    {
        $item = LayananPublik::findOrFail($id);

        return view('admin.layanan.show', [
            'title' => 'Detail Layanan Publik',
            'item' => $item
        ]);
    }



    // =========================================================
    // ADMIN DOWNLOAD FILE
    // =========================================================
    public function downloadFile($filename)
    {
        $path = 'public/berkas-layanan/' . $filename;

        if (!Storage::exists($path)) {
            abort(404, 'File tidak ditemukan');
        }

        return Storage::download($path);
    }



    // =========================================================
    // ADMIN DELETE RECORD
    // =========================================================
    public function destroy($id)
    {
        $item = LayananPublik::findOrFail($id);

        // hapus file
        if ($item->berkas) {
            foreach (json_decode($item->berkas, true) as $file) {
                Storage::disk('public')->delete($file);
            }
        }

        $item->delete();

        return redirect()
            ->back()
            ->with('success', 'Data berhasil dihapus');
    }
}
