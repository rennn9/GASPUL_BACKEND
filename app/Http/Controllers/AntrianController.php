<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Antrian;
use App\Models\Konsultasi;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use PDF;
use Carbon\Carbon;
use App\Services\TiketService; // ⬅️ Tambahkan ini


class AntrianController extends Controller
{
    // ===========================
    // SUBMIT ANTRIAN BARU
    // ===========================
    public function submit(Request $request)
    {
        try {
            // 1️⃣ Validasi input
            $validated = $request->validate([
                'nama'            => 'required|string|max:255',
                'no_hp'           => 'required|string|max:20',
                'alamat'          => 'required|string',
                'bidang_layanan'  => 'required|string',
                'layanan'         => 'required|string',
                'tanggal_daftar'  => 'required|date',
                'keterangan'      => 'nullable|string',
            ]);

            // 2️⃣ Generate nomor antrian (GLOBAL per tanggal)
            $count = Antrian::whereDate('tanggal_daftar', $validated['tanggal_daftar'])->count();
            $nomor_antrian = $count + 1;
            $validated['nomor_antrian'] = sprintf('%03d', $nomor_antrian);

            // 3️⃣ Generate QR Code Base64
            $qrContent = json_encode([
                'nomor_antrian'  => $validated['nomor_antrian'],
                'bidang_layanan' => $validated['bidang_layanan'],
                'tanggal_daftar' => $validated['tanggal_daftar'],
            ]);

            $validated['qr_code_data'] = base64_encode(
                QrCode::format('svg')->size(150)->generate($qrContent)
            );

            // 4️⃣ Status default = Diproses
            $validated['status'] = 'Diproses';

            // 5️⃣ Simpan ke database
            $antrian = Antrian::create($validated);

            // 6️⃣ Generate tiket PDF lewat TiketService
            $tiket = TiketService::generateTiket($antrian, false);


            // 🔟 Kirim respons JSON ke Flutter
            return response()->json([
                'success'        => true,
                'nomor_antrian'  => $antrian->nomor_antrian,
                'pdf_url'        => $tiket['pdf_url'],
                'qr_code_data'   => $tiket['qr_code_base64'],
            ]);
        } 
        catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $e->errors(),
            ], 422);
        } 
        catch (\Exception $e) {
            \Log::error('Error submit antrian: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses data',
                'error'   => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ], 500);
        }
    }

    // ===========================
    // TAMPILKAN SEMUA ANTRIAN
    // ===========================
    public function index()
    {
        $antrians = Antrian::orderBy('tanggal_daftar', 'desc')->get();
        return view('admin.antrian.antrian', compact('antrians'));
    }


    // ===========================
    // UPDATE STATUS (sinkron ke konsultasi)
    // ===========================
    public function updateStatus(Request $request)
    {
        $request->validate([
            'id'     => 'required|exists:antrian,id',
            'status' => 'required|in:Diproses,Selesai,Batal',
        ]);

        $antrian = Antrian::findOrFail($request->id);
        $antrian->status = $request->status;
        $antrian->save();

        // 🔄 Sinkronisasi ke tabel konsultasi
        if ($antrian->konsultasi_id) {
            $konsultasi = Konsultasi::find($antrian->konsultasi_id);

            if ($konsultasi) {
                switch ($request->status) {
                    case 'Diproses':
                        $konsultasi->status = 'proses';
                        break;
                    case 'Selesai':
                        $konsultasi->status = 'selesai';
                        break;
                    case 'Batal':
                        $konsultasi->status = 'batal';
                        break;
                }
                $konsultasi->save();
            }
        }

        return response()->json(['success' => true]);
    }


    // ===========================
    // PARTIAL TABEL (AJAX)
    // ===========================
    public function table(Request $request)
    {
        $filter = $request->query('filter', 'all');
        $date   = $request->query('date', null);

        $query = Antrian::query();

        switch ($filter) {
            case 'today':
                $query->whereDate('tanggal_daftar', now()->toDateString());
                break;
            case 'tomorrow':
                $query->whereDate('tanggal_daftar', now()->addDay()->toDateString());
                break;
            case 'custom':
                if ($date) $query->whereDate('tanggal_daftar', $date);
                break;
            case 'all':
            default:
                break;
        }

        $antrian = $query->orderBy('tanggal_daftar', 'asc')
                         ->orderBy('nomor_antrian', 'asc')
                         ->paginate(20);

        Carbon::setLocale('id');

        return view('admin.partials.antrian_table', compact('antrian'));
    }

public function delete(Request $request)
{
    $request->validate([
        'id' => 'required|exists:antrian,id'
    ]);

    $antrian = Antrian::findOrFail($request->id);

    try {
        // Hapus konsultasi terkait jika ada
        if ($antrian->konsultasi_id) {
            $konsultasi = Konsultasi::find($antrian->konsultasi_id);
            if ($konsultasi) {
                $konsultasi->delete();
            }
        }

        // Hapus antrian
        $antrian->delete();

        return response()->json(['success' => true]);
    } catch (\Exception $e) {
        \Log::error('Gagal hapus antrian: ' . $e->getMessage());
        return response()->json(['success' => false, 'message' => 'Gagal menghapus antrian']);
    }
}

    // ===========================
    // DOWNLOAD PDF DAFTAR
    // ===========================
    public function downloadPdfDaftar(Request $request)
    {
        Carbon::setLocale('id');
        setlocale(LC_TIME, 'id_ID.utf8');

        $filter = $request->query('filter', 'all');
        $date   = $request->query('date', null);

        $query = Antrian::query();

        switch ($filter) {
            case 'today':
                $query->whereDate('tanggal_daftar', now()->toDateString());
                break;
            case 'tomorrow':
                $query->whereDate('tanggal_daftar', now()->addDay()->toDateString());
                break;
            case 'custom':
                if ($date) $query->whereDate('tanggal_daftar', $date);
                break;
            case 'all':
            default:
                break;
        }

        $antrian = $query->orderBy('tanggal_daftar', 'desc')
                         ->orderBy('nomor_antrian', 'desc')
                         ->get();

        return PDF::loadView('admin.exports.antrian_pdf', [
            'antrians' => $antrian,
            'filter'   => $filter,
            'date'     => $date
        ])
        ->setPaper('a4', 'portrait')
        ->stream("Daftar_Antrian_{$filter}.pdf");
    }


    // ===========================
    // MONITOR ANTRIAN
    // ===========================
    public function monitor()
    {
        return view('admin.monitor');
    }


    public function monitorData()
    {
        $today = Carbon::today()->toDateString();

        $dalamProses = Antrian::whereDate('tanggal_daftar', $today)
            ->where('status', 'Diproses')
            ->orderBy('nomor_antrian', 'asc')
            ->get();

        $selesai = Antrian::whereDate('tanggal_daftar', $today)
            ->where('status', 'Selesai')
            ->orderBy('nomor_antrian', 'desc')
            ->get();

        $current = $dalamProses->first();

        return response()->json([
            'current'      => $current,
            'dalamProses'  => $dalamProses,
            'selesai'      => $selesai,
        ]);
    }
}
