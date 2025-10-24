<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Antrian;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use PDF;
use Carbon\Carbon;

class AntrianController extends Controller
{
    // Submit antrian baru
    public function submit(Request $request)
    {
        try {
            // 1️⃣ Validasi input
            $validated = $request->validate([
                'nama' => 'required|string|max:255',
                'no_hp' => 'required|string|max:20',
                'alamat' => 'required|string',
                'bidang_layanan' => 'required|string',
                'layanan' => 'required|string',
                'tanggal_daftar' => 'required|date',
                'keterangan' => 'nullable|string',
            ]);

            // 2️⃣ Generate nomor antrian (GLOBAL per tanggal)
            $count = Antrian::whereDate('tanggal_daftar', $validated['tanggal_daftar'])->count();
            $nomor_antrian = $count + 1;
            $validated['nomor_antrian'] = sprintf('%03d', $nomor_antrian);

            // 3️⃣ Generate QR code SVG base64 (tidak butuh Imagick)
            $qrContent = json_encode([
                'nomor_antrian' => $validated['nomor_antrian'],
                'bidang_layanan' => $validated['bidang_layanan'],
                'tanggal_daftar' => $validated['tanggal_daftar'],
            ]);
            // Gunakan SVG format yang tidak memerlukan Imagick extension
            $validated['qr_code_data'] = base64_encode(
                QrCode::format('svg')->size(150)->generate($qrContent)
            );

            // 4️⃣ Status default = Diproses
            $validated['status'] = 'Diproses';

            // 5️⃣ Simpan ke database
            $antrian = Antrian::create($validated);

            // 6️⃣ Set locale Carbon ke Indonesia
            Carbon::setLocale('id');

            // 7️⃣ Pastikan folder tiket ada
            $tiketPath = public_path('tiket');
            if (!file_exists($tiketPath)) {
                mkdir($tiketPath, 0755, true);
            }

            // 8️⃣ Generate PDF tiket
            // Lebar 80mm, tinggi 200mm
            $pdf = PDF::loadView('admin.exports.tiket_pdf', [
                'nomor'   => $antrian->nomor_antrian,
                'tanggal' => Carbon::parse($antrian->tanggal_daftar)->translatedFormat('l, d/m/Y'),
                'bidang'  => $antrian->bidang_layanan,
                'layanan' => $antrian->layanan,
                'qrCode'  => $antrian->qr_code_data,
            ])->setPaper([0, 0, 226.8, 567]); // ukuran dalam mm? atau px tergantung dompdf


            // 9️⃣ Simpan PDF ke public/tiket dengan nama unik
            $pdfFileName = $antrian->tanggal_daftar . '-' . $antrian->nomor_antrian . '.pdf';
            $pdfPath = public_path("tiket/{$pdfFileName}");
            $pdf->save($pdfPath);

            // 🔟 Kirim respons JSON ke Flutter
            return response()->json([
                'success' => true,
                'nomor_antrian' => $antrian->nomor_antrian,
                'pdf_url' => url("tiket/{$pdfFileName}"),
                'qr_code_data' => $antrian->qr_code_data,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error submit antrian: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses data',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    }

    // 🔸 Method index untuk menampilkan semua antrian
    public function index()
    {
        $antrians = Antrian::orderBy('tanggal_daftar', 'desc')->get();
        return view('admin.antrian.antrian', compact('antrians'));
    }

    // 🔸 Update status via AJAX
    public function updateStatus(Request $request)
    {
        $request->validate([
            'id'     => 'required|exists:antrian,id',
            'status' => 'required|in:Diproses,Selesai,Batal',
        ]);

        $antrian = Antrian::findOrFail($request->id);
        $antrian->status = $request->status;
        $antrian->save();

        return response()->json(['success' => true]);
    }

    // 🔸 Partial table untuk AJAX refresh
    public function table(Request $request)
    {
        $filter = $request->query('filter', 'all');
        $date   = $request->query('date', null);

        $query = Antrian::query();

        // Filter berdasarkan tab
        switch($filter){
            case 'today':
                $query->whereDate('tanggal_daftar', now()->toDateString());
                break;
            case 'tomorrow':
                $query->whereDate('tanggal_daftar', now()->addDay()->toDateString());
                break;
            case 'custom':
                if($date) $query->whereDate('tanggal_daftar', $date);
                break;
            case 'all':
            default:
                // tanpa filter tanggal
                break;
        }

        // Urutkan berdasarkan tanggal & nomor antrian dan gunakan pagination (20 data per halaman)
        $antrian = $query->orderBy('tanggal_daftar', 'desc')
                        ->orderBy('nomor_antrian', 'desc')
                        ->paginate(20);

        // Set locale Carbon supaya translatedFormat pakai bahasa Indonesia
        Carbon::setLocale('id');

        return view('admin.partials.antrian_table', compact('antrian'));
    }

public function downloadPdfDaftar(Request $request)
{
    // Set locale Carbon ke Indonesia
    Carbon::setLocale('id');
    setlocale(LC_TIME, 'id_ID.utf8'); // penting untuk DomPDF

    $filter = $request->query('filter', 'all');
    $date   = $request->query('date', null);

    $query = Antrian::query();

    switch($filter){
        case 'today':
            $query->whereDate('tanggal_daftar', now()->toDateString());
            break;
        case 'tomorrow':
            $query->whereDate('tanggal_daftar', now()->addDay()->toDateString());
            break;
        case 'custom':
            if($date) $query->whereDate('tanggal_daftar', $date);
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
    ])->setPaper('a4', 'portrait')
      ->stream("Daftar_Antrian_{$filter}.pdf");
}








}
