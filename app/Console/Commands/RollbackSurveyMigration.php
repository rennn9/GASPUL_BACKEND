<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Survey;
use App\Models\SurveyTemplate;
use App\Models\SurveyQuestion;
use App\Models\SurveyQuestionOption;
use App\Models\SurveyResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class RollbackSurveyMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'survey:rollback {--file= : Path ke backup file} {--force : Skip confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback survey migration dan restore data dari backup';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->error('⚠️  PERINGATAN: ROLLBACK SURVEY MIGRATION');
        $this->newLine();
        $this->warn('Proses ini akan:');
        $this->warn('1. Menghapus semua data di tabel survey baru (responses, questions, options, templates)');
        $this->warn('2. Restore data survey lama dari backup');
        $this->warn('3. Rollback 5 migrations terbaru');
        $this->newLine();

        // Confirmation unless --force
        if (!$this->option('force')) {
            if (!$this->confirm('Apakah Anda yakin ingin melanjutkan rollback?', false)) {
                $this->info('❌ Rollback dibatalkan.');
                return 0;
            }
        }

        try {
            // 1. Find backup file
            $backupFile = $this->option('file');

            if (!$backupFile) {
                $this->info('🔍 Mencari backup file terbaru...');
                $backupDir = storage_path('app/backups');

                if (!File::exists($backupDir)) {
                    $this->error('❌ Direktori backup tidak ditemukan: ' . $backupDir);
                    $this->comment('   Jalankan: php artisan survey:export-old-data terlebih dahulu');
                    return 1;
                }

                // Get latest backup file
                $files = File::files($backupDir);
                $backupFiles = array_filter($files, function ($file) {
                    return str_contains($file->getFilename(), 'survey_backup_') &&
                           $file->getExtension() === 'json';
                });

                if (empty($backupFiles)) {
                    $this->error('❌ Tidak ada backup file ditemukan di: ' . $backupDir);
                    $this->comment('   Jalankan: php artisan survey:export-old-data terlebih dahulu');
                    return 1;
                }

                // Sort by modification time, get latest
                usort($backupFiles, function ($a, $b) {
                    return $b->getMTime() - $a->getMTime();
                });

                $backupFile = $backupFiles[0]->getPathname();
                $this->info("📄 Menggunakan backup: " . $backupFiles[0]->getFilename());
            }

            // 2. Verify backup file exists
            if (!File::exists($backupFile)) {
                $this->error("❌ Backup file tidak ditemukan: {$backupFile}");
                return 1;
            }

            // 3. Read and parse backup file
            $this->info('📖 Membaca backup file...');
            $backupContent = File::get($backupFile);
            $backupData = json_decode($backupContent, true);

            if (!$backupData || !isset($backupData['surveys'])) {
                $this->error('❌ Format backup file tidak valid!');
                return 1;
            }

            $totalBackupSurveys = count($backupData['surveys']);
            $this->info("✅ Backup valid: {$totalBackupSurveys} surveys ditemukan");
            $this->info("   Exported at: {$backupData['exported_at']}");
            $this->newLine();

            // Final confirmation
            if (!$this->option('force')) {
                if (!$this->confirm("Restore {$totalBackupSurveys} surveys dari backup?", false)) {
                    $this->info('❌ Rollback dibatalkan.');
                    return 0;
                }
            }

            // 4. Start rollback process with transaction
            DB::beginTransaction();

            try {
                // 4a. Clear survey_responses table
                $this->info('🗑️  Menghapus data survey_responses...');
                if (Schema::hasTable('survey_responses')) {
                    DB::table('survey_responses')->truncate();
                }

                // 4b. Clear survey_question_options table
                $this->info('🗑️  Menghapus data survey_question_options...');
                if (Schema::hasTable('survey_question_options')) {
                    DB::table('survey_question_options')->truncate();
                }

                // 4c. Clear survey_questions table
                $this->info('🗑️  Menghapus data survey_questions...');
                if (Schema::hasTable('survey_questions')) {
                    DB::table('survey_questions')->truncate();
                }

                // 4d. Clear survey_templates table
                $this->info('🗑️  Menghapus data survey_templates...');
                if (Schema::hasTable('survey_templates')) {
                    DB::table('survey_templates')->truncate();
                }

                // 4e. Set survey_template_id to NULL in surveys table
                $this->info('🔄 Reset survey_template_id di tabel surveys...');
                DB::table('surveys')->update(['survey_template_id' => null]);

                // 4f. Restore surveys.jawaban from backup
                $this->info('📥 Restore data surveys dari backup...');
                $bar = $this->output->createProgressBar($totalBackupSurveys);
                $bar->start();

                foreach ($backupData['surveys'] as $surveyData) {
                    // Update existing survey or skip if not exists
                    $exists = DB::table('surveys')->where('id', $surveyData['id'])->exists();

                    if ($exists) {
                        DB::table('surveys')
                            ->where('id', $surveyData['id'])
                            ->update([
                                'jawaban' => json_encode($surveyData['jawaban']),
                                'survey_template_id' => null,
                            ]);
                    }

                    $bar->advance();
                }

                $bar->finish();
                $this->newLine(2);

                DB::commit();

                $this->info('✅ Data berhasil di-restore dari backup!');
                $this->newLine();

                // 5. Rollback migrations
                $this->warn('🔄 Rollback migrations...');
                $this->warn('   PENTING: Jalankan manual setelah ini:');
                $this->warn('   php artisan migrate:rollback --step=5');
                $this->newLine();
                $this->comment('💡 Migrations yang perlu di-rollback:');
                $this->comment('   1. add_survey_template_id_to_surveys_table');
                $this->comment('   2. create_survey_responses_table');
                $this->comment('   3. create_survey_question_options_table');
                $this->comment('   4. create_survey_questions_table');
                $this->comment('   5. create_survey_templates_table');

                $this->newLine();
                $this->info('✅ Rollback data selesai!');
                $this->info("📊 {$totalBackupSurveys} surveys telah di-restore");
                $this->newLine();
                $this->comment('⚠️  Jangan lupa rollback migrations dengan:');
                $this->comment('   php artisan migrate:rollback --step=5');

                return 0;

            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }

        } catch (\Exception $e) {
            $this->error('❌ Error saat rollback:');
            $this->error($e->getMessage());
            $this->newLine();
            $this->error('Stack trace:');
            $this->line($e->getTraceAsString());

            \Log::error('[RollbackSurveyMigration] Rollback failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }
}
