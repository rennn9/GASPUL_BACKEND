<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Survey;
use App\Models\SurveyTemplate;
use App\Models\SurveyQuestion;
use App\Models\SurveyQuestionOption;
use App\Models\SurveyResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SurveyTemplateMigrationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔄 Memulai migration data survey ke sistem baru...');
        $this->command->newLine();

        DB::beginTransaction();

        try {
            // 1. Create Template v1 (Legacy)
            $this->command->info('📋 Membuat template legacy...');
            $template = SurveyTemplate::create([
                'nama' => 'Template IKM 2024 (Legacy)',
                'deskripsi' => 'Template survey kepuasan masyarakat tahun 2024 - data legacy dari sistem hardcoded',
                'versi' => 1,
                'is_active' => true,
                'created_by_user_id' => null,
            ]);
            $this->command->info("✅ Template created: ID {$template->id}");

            // 2. Create 9 Questions with U1-U9 mapping
            $this->command->info('📝 Membuat 9 pertanyaan standar IKM...');
            $questions = $this->createQuestions($template->id);
            $this->command->info("✅ {$questions->count()} pertanyaan berhasil dibuat");

            // 3. Create Options for each question
            $this->command->info('✏️  Membuat pilihan jawaban untuk setiap pertanyaan...');
            $this->createOptions($questions);
            $this->command->info('✅ Semua pilihan jawaban berhasil dibuat');

            // 4. Migrate existing survey data
            $this->command->info('🔄 Migrate data survey existing...');
            $migratedCount = $this->migrateSurveyData($template->id, $questions);
            $this->command->info("✅ {$migratedCount} survey berhasil dimigrate");

            DB::commit();

            $this->command->newLine();
            $this->command->info('✅ Migration selesai!');
            $this->command->info("📊 Template ID: {$template->id}");
            $this->command->info("📊 Total pertanyaan: {$questions->count()}");
            $this->command->info("📊 Total survey dimigrate: {$migratedCount}");

        } catch (\Exception $e) {
            DB::rollback();
            $this->command->error('❌ Migration gagal!');
            $this->command->error($e->getMessage());
            $this->command->newLine();
            $this->command->error('Stack trace:');
            $this->command->line($e->getTraceAsString());

            Log::error('[SurveyTemplateMigrationSeeder] Migration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Create 9 standard IKM questions
     */
    private function createQuestions($templateId)
    {
        $questionsData = [
            [
                'pertanyaan_text' => 'Bagaimana pendapat Saudara tentang kesesuaian persyaratan pelayanan dengan jenis pelayanannya?',
                'kode_unsur' => 'U1',
                'urutan' => 1,
            ],
            [
                'pertanyaan_text' => 'Bagaimana pemahaman Saudara tentang kemudahan prosedur pelayanan di unit ini?',
                'kode_unsur' => 'U2',
                'urutan' => 2,
            ],
            [
                'pertanyaan_text' => 'Bagaimana pendapat Saudara tentang kecepatan waktu dalam memberikan pelayanan?',
                'kode_unsur' => 'U3',
                'urutan' => 3,
            ],
            [
                'pertanyaan_text' => 'Bagaimana pendapat Saudara tentang kewajaran biaya/tarif dalam pelayanan?',
                'kode_unsur' => 'U4',
                'urutan' => 4,
            ],
            [
                'pertanyaan_text' => 'Bagaimana pendapat Saudara tentang kesesuaian produk pelayanan antara yang tercantum dalam standar pelayanan dengan hasil yang diberikan?',
                'kode_unsur' => 'U5',
                'urutan' => 5,
            ],
            [
                'pertanyaan_text' => 'Bagaimana pendapat Saudara tentang kompetensi/kemampuan petugas dalam pelayanan?',
                'kode_unsur' => 'U6',
                'urutan' => 6,
            ],
            [
                'pertanyaan_text' => 'Bagaimana pendapat Saudara tentang perilaku petugas dalam pelayanan terkait kesopanan dan keramahan?',
                'kode_unsur' => 'U7',
                'urutan' => 7,
            ],
            [
                'pertanyaan_text' => 'Bagaimana pendapat Saudara tentang kualitas sarana dan prasarana?',
                'kode_unsur' => 'U8',
                'urutan' => 8,
            ],
            [
                'pertanyaan_text' => 'Bagaimana pendapat Saudara tentang penanganan pengaduan pengguna layanan?',
                'kode_unsur' => 'U9',
                'urutan' => 9,
            ],
        ];

        $questions = collect();
        foreach ($questionsData as $data) {
            $question = SurveyQuestion::create([
                'survey_template_id' => $templateId,
                'pertanyaan_text' => $data['pertanyaan_text'],
                'kode_unsur' => $data['kode_unsur'],
                'urutan' => $data['urutan'],
                'is_required' => true,
                'is_text_input' => false,
            ]);
            $questions->push($question);
        }

        return $questions;
    }

    /**
     * Create options for each question based on hardcoded Flutter/React data
     */
    private function createOptions($questions)
    {
        // Mapping options per question (from Flutter code)
        $optionsMapping = [
            'U1' => [ // Kesesuaian
                ['jawaban_text' => 'Tidak sesuai', 'poin' => 1, 'urutan' => 1],
                ['jawaban_text' => 'Kurang sesuai', 'poin' => 2, 'urutan' => 2],
                ['jawaban_text' => 'Sesuai', 'poin' => 3, 'urutan' => 3],
                ['jawaban_text' => 'Sangat sesuai', 'poin' => 4, 'urutan' => 4],
            ],
            'U2' => [ // Kemudahan
                ['jawaban_text' => 'Tidak mudah', 'poin' => 1, 'urutan' => 1],
                ['jawaban_text' => 'Kurang mudah', 'poin' => 2, 'urutan' => 2],
                ['jawaban_text' => 'Mudah', 'poin' => 3, 'urutan' => 3],
                ['jawaban_text' => 'Sangat mudah', 'poin' => 4, 'urutan' => 4],
            ],
            'U3' => [ // Kecepatan
                ['jawaban_text' => 'Tidak cepat', 'poin' => 1, 'urutan' => 1],
                ['jawaban_text' => 'Kurang cepat', 'poin' => 2, 'urutan' => 2],
                ['jawaban_text' => 'Cepat', 'poin' => 3, 'urutan' => 3],
                ['jawaban_text' => 'Sangat cepat', 'poin' => 4, 'urutan' => 4],
            ],
            'U4' => [ // Biaya (reversed scale)
                ['jawaban_text' => 'Sangat mahal', 'poin' => 1, 'urutan' => 1],
                ['jawaban_text' => 'Cukup mahal', 'poin' => 2, 'urutan' => 2],
                ['jawaban_text' => 'Murah', 'poin' => 3, 'urutan' => 3],
                ['jawaban_text' => 'Gratis', 'poin' => 4, 'urutan' => 4],
            ],
            'U5' => [ // Kesesuaian produk (sama dengan U1)
                ['jawaban_text' => 'Tidak sesuai', 'poin' => 1, 'urutan' => 1],
                ['jawaban_text' => 'Kurang sesuai', 'poin' => 2, 'urutan' => 2],
                ['jawaban_text' => 'Sesuai', 'poin' => 3, 'urutan' => 3],
                ['jawaban_text' => 'Sangat sesuai', 'poin' => 4, 'urutan' => 4],
            ],
            'U6' => [ // Kompetensi
                ['jawaban_text' => 'Tidak kompeten', 'poin' => 1, 'urutan' => 1],
                ['jawaban_text' => 'Kurang kompeten', 'poin' => 2, 'urutan' => 2],
                ['jawaban_text' => 'Kompeten', 'poin' => 3, 'urutan' => 3],
                ['jawaban_text' => 'Sangat kompeten', 'poin' => 4, 'urutan' => 4],
            ],
            'U7' => [ // Perilaku
                ['jawaban_text' => 'Tidak sopan dan ramah', 'poin' => 1, 'urutan' => 1],
                ['jawaban_text' => 'Kurang sopan dan ramah', 'poin' => 2, 'urutan' => 2],
                ['jawaban_text' => 'Sopan dan ramah', 'poin' => 3, 'urutan' => 3],
                ['jawaban_text' => 'Sangat sopan dan ramah', 'poin' => 4, 'urutan' => 4],
            ],
            'U8' => [ // Sarana
                ['jawaban_text' => 'Buruk', 'poin' => 1, 'urutan' => 1],
                ['jawaban_text' => 'Cukup', 'poin' => 2, 'urutan' => 2],
                ['jawaban_text' => 'Baik', 'poin' => 3, 'urutan' => 3],
                ['jawaban_text' => 'Sangat Baik', 'poin' => 4, 'urutan' => 4],
            ],
            'U9' => [ // Pengaduan
                ['jawaban_text' => 'Tidak ada', 'poin' => 1, 'urutan' => 1],
                ['jawaban_text' => 'Ada tetapi tidak berfungsi', 'poin' => 2, 'urutan' => 2],
                ['jawaban_text' => 'Berfungsi kurang maksimal', 'poin' => 3, 'urutan' => 3],
                ['jawaban_text' => 'Dikelola dengan baik', 'poin' => 4, 'urutan' => 4],
            ],
        ];

        foreach ($questions as $question) {
            $options = $optionsMapping[$question->kode_unsur] ?? [];

            foreach ($options as $optionData) {
                SurveyQuestionOption::create([
                    'survey_question_id' => $question->id,
                    'jawaban_text' => $optionData['jawaban_text'],
                    'poin' => $optionData['poin'],
                    'urutan' => $optionData['urutan'],
                ]);
            }
        }
    }

    /**
     * Migrate existing survey data to new system
     */
    private function migrateSurveyData($templateId, $questions)
    {
        $surveys = Survey::all();
        $migratedCount = 0;
        $skippedCount = 0;

        // Create mapping of question text to question ID
        $questionMapping = [];
        foreach ($questions as $question) {
            $questionMapping[$question->pertanyaan_text] = $question;
        }

        // Create mapping of option text to option ID (per question)
        $optionMapping = [];
        foreach ($questions as $question) {
            $optionMapping[$question->id] = [];
            foreach ($question->options as $option) {
                $optionMapping[$question->id][$option->jawaban_text] = $option;
            }
        }

        $bar = $this->command->getOutput()->createProgressBar($surveys->count());
        $bar->start();

        foreach ($surveys as $survey) {
            try {
                // Parse jawaban JSON
                $jawaban = $survey->jawaban;

                // Handle double-encoded JSON
                if (is_string($jawaban)) {
                    $jawaban = json_decode($jawaban, true);
                }
                if (is_string($jawaban)) {
                    $jawaban = json_decode($jawaban, true);
                }

                if (!is_array($jawaban)) {
                    $skippedCount++;
                    $bar->advance();
                    continue;
                }

                // Update survey template_id
                $survey->update(['survey_template_id' => $templateId]);

                // Migrate each answer to survey_responses
                foreach ($jawaban as $pertanyaan => $jawabanData) {
                    // Skip if not matching any question
                    if (!isset($questionMapping[$pertanyaan])) {
                        continue;
                    }

                    $question = $questionMapping[$pertanyaan];

                    // Get jawaban text and nilai
                    $jawabanText = is_array($jawabanData) && isset($jawabanData['jawaban'])
                        ? $jawabanData['jawaban']
                        : (is_string($jawabanData) ? $jawabanData : null);

                    $nilai = is_array($jawabanData) && isset($jawabanData['nilai'])
                        ? intval($jawabanData['nilai'])
                        : null;

                    // Find matching option
                    $optionId = null;
                    if ($jawabanText && isset($optionMapping[$question->id][$jawabanText])) {
                        $option = $optionMapping[$question->id][$jawabanText];
                        $optionId = $option->id;
                        // Use option's poin if nilai not provided
                        if ($nilai === null) {
                            $nilai = $option->poin;
                        }
                    }

                    // Create survey response
                    SurveyResponse::create([
                        'survey_id' => $survey->id,
                        'survey_question_id' => $question->id,
                        'survey_option_id' => $optionId,
                        'jawaban_text' => $jawabanText,
                        'poin' => $nilai,
                    ]);
                }

                $migratedCount++;

            } catch (\Exception $e) {
                Log::warning('[SurveyMigration] Failed to migrate survey', [
                    'survey_id' => $survey->id,
                    'error' => $e->getMessage(),
                ]);
                $skippedCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();

        if ($skippedCount > 0) {
            $this->command->warn("⚠️  {$skippedCount} survey dilewati karena format data tidak valid");
        }

        return $migratedCount;
    }
}
