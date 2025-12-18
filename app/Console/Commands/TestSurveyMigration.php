<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SurveyTemplate;
use App\Models\SurveyQuestion;
use App\Models\SurveyQuestionOption;
use App\Models\SurveyResponse;
use App\Models\Survey;

class TestSurveyMigration extends Command
{
    protected $signature = 'survey:test-migration';
    protected $description = 'Test dan verify hasil migration survey';

    public function handle()
    {
        $this->info('🧪 Testing Survey Migration Results...');
        $this->newLine();

        // 1. Check Templates
        $this->info('📋 Survey Templates:');
        $templates = SurveyTemplate::all();
        foreach ($templates as $template) {
            $this->line("  ID: {$template->id}");
            $this->line("  Nama: {$template->nama}");
            $this->line("  Versi: {$template->versi}");
            $this->line("  Active: " . ($template->is_active ? 'Yes' : 'No'));
            $this->line("  Questions: {$template->questions()->count()}");
        }
        $this->newLine();

        // 2. Check Questions
        $this->info('📝 Questions Summary:');
        $questions = SurveyQuestion::with('options')->get();
        foreach ($questions as $question) {
            $this->line("  {$question->kode_unsur}: " . substr($question->pertanyaan_text, 0, 60) . '...');
            $this->line("     Options: {$question->options->count()}, Required: " . ($question->is_required ? 'Yes' : 'No'));
        }
        $this->newLine();

        // 3. Check one question with options detail
        $this->info('🔍 Sample Question (U1) with Options:');
        $q1 = SurveyQuestion::where('kode_unsur', 'U1')->with('options')->first();
        if ($q1) {
            $this->line("  Question: {$q1->pertanyaan_text}");
            foreach ($q1->options as $option) {
                $this->line("     [{$option->poin}] {$option->jawaban_text}");
            }
        }
        $this->newLine();

        // 4. Check Survey Responses
        $this->info('📊 Survey Responses:');
        $totalResponses = SurveyResponse::count();
        $this->line("  Total Responses: {$totalResponses}");

        $responsesPerSurvey = SurveyResponse::selectRaw('survey_id, COUNT(*) as count')
            ->groupBy('survey_id')
            ->get();
        $this->line("  Surveys with responses: {$responsesPerSurvey->count()}");
        $this->line("  Average responses per survey: " . round($responsesPerSurvey->avg('count'), 2));
        $this->newLine();

        // 5. Check specific survey
        $this->info('🔍 Sample Survey Check:');
        $survey = Survey::with(['responses.question', 'responses.option'])->first();
        if ($survey) {
            $this->line("  Survey ID: {$survey->id}");
            $this->line("  Responden: {$survey->nama_responden}");
            $this->line("  Template ID: {$survey->survey_template_id}");
            $this->line("  Total Responses: {$survey->responses->count()}");

            $this->newLine();
            $this->line("  Response Details:");
            foreach ($survey->responses->take(3) as $response) {
                $this->line("    {$response->question->kode_unsur}: {$response->jawaban_text} (poin: {$response->poin})");
            }
        }
        $this->newLine();

        // 6. Summary
        $this->info('✅ Migration Verification Summary:');
        $this->table(
            ['Item', 'Count', 'Expected', 'Status'],
            [
                ['Templates', SurveyTemplate::count(), '1', SurveyTemplate::count() == 1 ? '✅' : '❌'],
                ['Questions', SurveyQuestion::count(), '9', SurveyQuestion::count() == 9 ? '✅' : '❌'],
                ['Options', SurveyQuestionOption::count(), '36', SurveyQuestionOption::count() == 36 ? '✅' : '❌'],
                ['Surveys Migrated', Survey::whereNotNull('survey_template_id')->count(), '9', Survey::whereNotNull('survey_template_id')->count() == 9 ? '✅' : '❌'],
                ['Responses', SurveyResponse::count(), '>= 9', SurveyResponse::count() >= 9 ? '✅' : '❌'],
            ]
        );

        $this->newLine();
        $this->info('🎉 Migration test completed!');

        return 0;
    }
}
