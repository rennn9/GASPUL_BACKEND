<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Survey;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class ExportOldSurveyData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'survey:export-old-data {--path= : Custom backup path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export survey data lama sebelum migration ke sistem baru (untuk backup dan rollback)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Memulai export data survey lama...');
        $this->newLine();

        try {
            // 1. Cek apakah ada data survey
            $totalSurveys = Survey::count();

            if ($totalSurveys === 0) {
                $this->warn('⚠️  Tidak ada data survey untuk di-export.');
                return 0;
            }

            $this->info("📊 Ditemukan {$totalSurveys} survey untuk di-export");

            // 2. Ambil semua data survey
            $surveys = Survey::all();

            // 3. Prepare backup data structure
            $backupData = [
                'exported_at' => Carbon::now()->toISOString(),
                'total_surveys' => $totalSurveys,
                'laravel_version' => app()->version(),
                'surveys' => $surveys->map(function ($survey) {
                    return [
                        'id' => $survey->id,
                        'antrian_id' => $survey->antrian_id,
                        'nomor_antrian' => $survey->nomor_antrian,
                        'layanan_publik_id' => $survey->layanan_publik_id,
                        'nama_responden' => $survey->nama_responden,
                        'no_hp_wa' => $survey->no_hp_wa,
                        'usia' => $survey->usia,
                        'jenis_kelamin' => $survey->jenis_kelamin,
                        'pendidikan' => $survey->pendidikan,
                        'pekerjaan' => $survey->pekerjaan,
                        'bidang' => $survey->bidang,
                        'tanggal' => $survey->tanggal ? $survey->tanggal->toDateString() : null,
                        'jawaban' => $survey->jawaban, // Already array from cast
                        'saran' => $survey->saran,
                        'surveyed_at' => $survey->surveyed_at ? $survey->surveyed_at->toISOString() : null,
                        'created_at' => $survey->created_at->toISOString(),
                        'updated_at' => $survey->updated_at->toISOString(),
                    ];
                })->toArray(),
            ];

            // 4. Determine backup path
            $customPath = $this->option('path');
            $backupDir = $customPath ?: storage_path('app/backups');

            // Create directory if not exists
            if (!File::exists($backupDir)) {
                File::makeDirectory($backupDir, 0755, true);
                $this->info("📁 Membuat direktori backup: {$backupDir}");
            }

            // 5. Generate filename with timestamp
            $timestamp = Carbon::now()->format('YmdHis');
            $filename = "survey_backup_{$timestamp}.json";
            $filepath = $backupDir . DIRECTORY_SEPARATOR . $filename;

            // 6. Write JSON file
            $jsonContent = json_encode($backupData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            File::put($filepath, $jsonContent);

            // 7. Verify file created
            if (File::exists($filepath)) {
                $fileSize = File::size($filepath);
                $fileSizeKB = round($fileSize / 1024, 2);

                $this->newLine();
                $this->info('✅ Export berhasil!');
                $this->info("📄 File: {$filename}");
                $this->info("📍 Path: {$filepath}");
                $this->info("📦 Size: {$fileSizeKB} KB");
                $this->info("📊 Total: {$totalSurveys} surveys");
                $this->newLine();
                $this->comment('💾 Backup ini dapat digunakan untuk rollback jika terjadi masalah.');
                $this->comment('   Gunakan: php artisan survey:rollback');

                return 0;
            } else {
                $this->error('❌ Gagal membuat file backup!');
                return 1;
            }

        } catch (\Exception $e) {
            $this->error('❌ Error saat export data:');
            $this->error($e->getMessage());
            $this->newLine();
            $this->error('Stack trace:');
            $this->line($e->getTraceAsString());

            \Log::error('[ExportOldSurveyData] Export failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }
}
