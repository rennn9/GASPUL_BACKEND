<?php

namespace App\Services;

use App\Models\LayananPublik;
use App\Models\StatusLayanan;
use PhpOffice\PhpWord\TemplateProcessor;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DocumentService
{
    /**
     * Generate Bukti Terima DOCX untuk layanan publik
     *
     * @param LayananPublik $layanan
     * @return array ['file_path' => string, 'file_name' => string]
     * @throws \Exception
     */
    public static function generateBuktiTerima(LayananPublik $layanan): array
    {
        try {
            Log::info('[DocumentService] Start generating Bukti Terima', [
                'layanan_id' => $layanan->id,
                'no_registrasi' => $layanan->no_registrasi,
            ]);

            // 1. Load template
            $templatePath = resource_path('templates/bukti_terima_ptsp.docx');

            if (!file_exists($templatePath)) {
                throw new \Exception("Template file not found: {$templatePath}");
            }

            Log::info('[DocumentService] Template file found', ['path' => $templatePath]);

            // 2. Get status "Menunggu Verifikasi Bidang" (yang paling awal)
            $statusVerifikasi = StatusLayanan::where('layanan_id', $layanan->id)
                ->whereRaw('LOWER(status) = ?', ['menunggu verifikasi bidang'])
                ->orderBy('created_at', 'asc')
                ->first();

            if (!$statusVerifikasi) {
                throw new \Exception("Status 'Menunggu Verifikasi Bidang' tidak ditemukan untuk layanan ID: {$layanan->id}");
            }

            Log::info('[DocumentService] Status verifikasi found', [
                'status_id' => $statusVerifikasi->id,
                'operator_nama' => $statusVerifikasi->operator_nama,
            ]);

            // 3. Prepare data dari layanan_publik
            $nomor_pemohon = $layanan->no_registrasi ?? '-';
            $layanan_value = $layanan->layanan ?? '-';
            $email = $layanan->email ?? '-';
            $nama = $layanan->nama ?? '-';
            $telepon = $layanan->telepon ?? '-';

            // 4. Prepare data operator (dengan fallback untuk data lama)
            $contactPerson = $statusVerifikasi->operator_nama;
            if (empty($contactPerson) && $statusVerifikasi->user_id) {
                $contactPerson = $statusVerifikasi->user->name ?? 'N/A';
                Log::info('[DocumentService] Using fallback for contact_person from user relation');
            }
            $contactPerson = $contactPerson ?? 'N/A';

            $contactPhone = $statusVerifikasi->operator_no_hp;
            if (empty($contactPhone) && $statusVerifikasi->user_id) {
                $contactPhone = $statusVerifikasi->user->no_hp ?? 'N/A';
                Log::info('[DocumentService] Using fallback for contact_phone from user relation');
            }
            $contactPhone = $contactPhone ?? 'N/A';

            // 5. Prepare tanggal (format Indonesia)
            Carbon::setLocale('id');
            $tanggal = Carbon::now()->translatedFormat('d F Y');

            // 6. Load template processor
            $templateProcessor = new TemplateProcessor($templatePath);

            // 7. Set values untuk mail merge
            $templateProcessor->setValue('nomor_pemohon', $nomor_pemohon);
            $templateProcessor->setValue('layanan', $layanan_value);
            $templateProcessor->setValue('email', $email);
            $templateProcessor->setValue('nama', $nama);
            $templateProcessor->setValue('telepon', $telepon);
            $templateProcessor->setValue('contact_person', $contactPerson);
            $templateProcessor->setValue('nomor_telepon', $contactPhone);
            $templateProcessor->setValue('tanggal', $tanggal);

            Log::info('[DocumentService] Mail merge values set', [
                'nomor_pemohon' => $nomor_pemohon,
                'layanan' => $layanan_value,
                'email' => $email,
                'nama' => $nama,
                'telepon' => $telepon,
                'contact_person' => $contactPerson,
                'nomor_telepon' => $contactPhone,
                'tanggal' => $tanggal,
            ]);

            // 8. Generate unique filename
            $timestamp = Carbon::now()->format('YmdHis');
            $safeNoReg = preg_replace('/[^A-Za-z0-9\-]/', '_', $layanan->no_registrasi);
            $fileName = "BuktiTerima_{$safeNoReg}_{$timestamp}.docx";

            // 9. Save to temp folder
            $tempPath = storage_path("app/temp/{$fileName}");

            // Ensure temp directory exists
            if (!is_dir(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
                Log::info('[DocumentService] Created temp directory', ['path' => dirname($tempPath)]);
            }

            $templateProcessor->saveAs($tempPath);

            Log::info('[DocumentService] Bukti Terima generated successfully', [
                'layanan_id' => $layanan->id,
                'file_name' => $fileName,
                'file_path' => $tempPath,
            ]);

            return [
                'file_path' => $tempPath,
                'file_name' => $fileName,
            ];

        } catch (\Throwable $e) {
            Log::error('[DocumentService] Failed to generate Bukti Terima', [
                'layanan_id' => $layanan->id ?? null,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }
}
