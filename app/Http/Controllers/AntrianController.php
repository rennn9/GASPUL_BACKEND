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

        // 3️⃣ Generate QR code PNG base64
        $qrContent = json_encode([
            'nomor_antrian' => $validated['nomor_antrian'],
            'bidang_layanan' => $validated['bidang_layanan'],
            'tanggal_daftar' => $validated['tanggal_daftar'],
        ]);
        $validated['qr_code_data'] = base64_encode(
            QrCode::format('png')->size(150)->generate($qrContent)
        );

        // 4️⃣ Status default = Diproses
        $validated['status'] = 'Diproses';

        // 5️⃣ Simpan ke database
        $antrian = Antrian::create($validated);

        // 6️⃣ Set locale Carbon ke Indonesia
        Carbon::setLocale('id');

        // 7️⃣ Generate PDF tiket
        // Lebar 80mm, tinggi 200mm
        $pdf = PDF::loadView('admin.exports.tiket_pdf', [
            'nomor'   => $antrian->nomor_antrian,
            'tanggal' => Carbon::parse($antrian->tanggal_daftar)->translatedFormat('l, d/m/Y'),
            'bidang'  => $antrian->bidang_layanan,
            'layanan' => $antrian->layanan,
            'qrCode'  => $antrian->qr_code_data,
        ])->setPaper([0, 0, 226.8, 567]); // ukuran dalam mm? atau px tergantung dompdf


        // 8️⃣ Simpan PDF ke public/tiket dengan nama unik
        $pdfFileName = $antrian->tanggal_daftar . '-' . $antrian->nomor_antrian . '.pdf';
        $pdfPath = public_path("tiket/{$pdfFileName}");
        $pdf->save($pdfPath);

        // 9️⃣ Kirim respons JSON ke Flutter
        return response()->json([
            'success' => true,
            'nomor_antrian' => $antrian->nomor_antrian,
            'pdf_url' => url("tiket/{$pdfFileName}"),
            'qr_code_data' => $antrian->qr_code_data,
        ]);
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
    public function table()
    {
        $antrian = Antrian::latest()->get();

        // Set locale supaya translatedFormat tetap pakai bahasa Indonesia
        Carbon::setLocale('id');

        return view('admin.partials.antrian_table', compact('antrian'));
    }
}
