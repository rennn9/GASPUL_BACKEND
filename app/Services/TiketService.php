<?php

namespace App\Services;

use App\Models\Antrian;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class TiketService
{
    /**
     * Generate tiket PDF untuk data Antrian dan simpan ke folder public/tiket.
     *
     * @param \App\Models\Antrian $antrian
     * @return array ['pdf_path' => string, 'pdf_url' => string, 'qr_code_base64' => string]
     */
public static function generateTiket(Antrian $antrian, bool $isKonsultasi = false)
{
    try {
        Carbon::setLocale('id');

        // Pastikan folder tiket ada
        $tiketPath = public_path('tiket');
        if (!File::exists($tiketPath)) {
            File::makeDirectory($tiketPath, 0755, true);
        }

        // QR code
        $qrContent = json_encode([
            'nomor_antrian'  => $antrian->nomor_antrian,
            'bidang_layanan' => $antrian->bidang_layanan,
            'tanggal_daftar' => Carbon::parse($antrian->tanggal_daftar)->toDateString(),
        ]);

        $qrBase64 = base64_encode(
            QrCode::format('svg')->size(150)->generate($qrContent)
        );

        // Tentukan title layanan/perihal
        $layananValue = $isKonsultasi && $antrian->konsultasi
            ? $antrian->konsultasi->perihal
            : $antrian->layanan;

        // Generate PDF
$pdf = Pdf::loadView('admin.exports.tiket_pdf', [
    'nomor'         => $antrian->nomor_antrian,
    'tanggal'       => Carbon::parse($antrian->tanggal_daftar)->translatedFormat('l, d/m/Y'),
    'bidang'        => $antrian->bidang_layanan,
    'layanan'       => $layananValue, // value perihal untuk konsultasi
    'isKonsultasi'  => $isKonsultasi, // <- kirim ke view
    'qrCode'        => $qrBase64,
])->setPaper([0, 0, 226.8, 567]);


        // Simpan PDF
        $safeDate = Carbon::parse($antrian->tanggal_daftar)->format('Y-m-d');
        $pdfFileName = "{$safeDate}-{$antrian->nomor_antrian}.pdf";
        $pdfPath = "{$tiketPath}/{$pdfFileName}";

        $pdf->save($pdfPath);

        Log::info('Tiket PDF berhasil dibuat', [
            'antrian_id' => $antrian->id,
            'file' => $pdfPath,
        ]);

        return [
            'pdf_path' => $pdfPath,
            'pdf_url' => url("tiket/{$pdfFileName}"),
            'qr_code_base64' => $qrBase64,
        ];

    } catch (\Throwable $th) {
        Log::error('Gagal generate tiket PDF', [
            'error' => $th->getMessage(),
            'line'  => $th->getLine(),
            'file'  => $th->getFile(),
        ]);
        throw $th;
    }
}

}
