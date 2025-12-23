<?php

namespace App\Http\Controllers;

use App\Models\SurveyTemplate;
use App\Models\SurveyQuestion;
use App\Models\SurveyQuestionOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SurveyQuestionController extends Controller
{
    /**
     * List pertanyaan untuk template tertentu
     */
    public function index($templateId)
    {
        Log::info('SurveyQuestionController@index called', ['template_id' => $templateId]);

        $template = SurveyTemplate::with(['questions.options' => function($query) {
            $query->orderBy('urutan');
        }])->findOrFail($templateId);

        $questions = $template->questions()->orderBy('urutan')->get();

        Log::info('Questions retrieved', [
            'template_id' => $templateId,
            'question_count' => $questions->count()
        ]);

        return view('admin.survey-question.index', compact('template', 'questions'));
    }

    /**
     * Tambah pertanyaan baru
     */
    public function store(Request $request)
    {
        Log::info('SurveyQuestionController@store called', ['request' => $request->all()]);

        $validated = $request->validate([
            'survey_template_id' => 'required|exists:survey_templates,id',
            'pertanyaan_text' => 'required|string',
            'unsur_pelayanan' => 'required|string|max:255',
            'kode_unsur' => 'nullable|string|max:10',
            'is_required' => 'nullable|boolean',
            'is_text_input' => 'nullable|boolean',
        ]);

        DB::beginTransaction();

        try {
            // Get next urutan
            $maxUrutan = SurveyQuestion::where('survey_template_id', $validated['survey_template_id'])
                ->max('urutan') ?? 0;

            $urutan = $maxUrutan + 1;

            $question = SurveyQuestion::create([
                'survey_template_id' => $validated['survey_template_id'],
                'pertanyaan_text' => $validated['pertanyaan_text'],
                'unsur_pelayanan' => $validated['unsur_pelayanan'],
                'kode_unsur' => 'U' . $urutan, // Auto-generate based on urutan
                'urutan' => $urutan,
                'is_required' => $validated['is_required'] ?? true,
                'is_text_input' => $validated['is_text_input'] ?? false,
            ]);

            DB::commit();

            Log::info('Question created successfully', [
                'id' => $question->id,
                'template_id' => $validated['survey_template_id']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pertanyaan berhasil ditambahkan',
                'data' => $question->load('options'),
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to create question', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan pertanyaan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update pertanyaan
     */
    public function update(Request $request, $id)
    {
        Log::info('SurveyQuestionController@update called', [
            'id' => $id,
            'request' => $request->all()
        ]);

        $validated = $request->validate([
            'pertanyaan_text' => 'required|string',
            'unsur_pelayanan' => 'required|string|max:255',
            'kode_unsur' => 'nullable|string|max:10',
            'is_required' => 'nullable|boolean',
            'is_text_input' => 'nullable|boolean',
        ]);

        try {
            $question = SurveyQuestion::findOrFail($id);

            $question->update([
                'pertanyaan_text' => $validated['pertanyaan_text'],
                'unsur_pelayanan' => $validated['unsur_pelayanan'],
                'kode_unsur' => 'U' . $question->urutan, // Auto-generate based on current urutan
                'is_required' => $validated['is_required'] ?? true,
                'is_text_input' => $validated['is_text_input'] ?? false,
            ]);

            Log::info('Question updated successfully', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Pertanyaan berhasil diperbarui',
                'data' => $question->fresh()->load('options'),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update question', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui pertanyaan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Hapus pertanyaan
     */
    public function destroy($id)
    {
        Log::info('SurveyQuestionController@destroy called', ['id' => $id]);

        DB::beginTransaction();

        try {
            $question = SurveyQuestion::findOrFail($id);
            $templateId = $question->survey_template_id;
            $deletedUrutan = $question->urutan;

            // Cek apakah pertanyaan ini sudah digunakan di survey_responses
            $usageCount = $question->responses()->count();
            if ($usageCount > 0) {
                Log::warning('Attempted to delete question with responses', [
                    'id' => $id,
                    'response_count' => $usageCount
                ]);

                return response()->json([
                    'success' => false,
                    'message' => "Pertanyaan tidak bisa dihapus karena sudah digunakan oleh {$usageCount} jawaban survey.",
                ], 422);
            }

            // Hapus question (cascade ke options)
            $question->delete();

            // Reorder urutan pertanyaan lain dan update kode_unsur
            $questionsToUpdate = SurveyQuestion::where('survey_template_id', $templateId)
                ->where('urutan', '>', $deletedUrutan)
                ->orderBy('urutan')
                ->get();

            foreach ($questionsToUpdate as $q) {
                $newUrutan = $q->urutan - 1;
                $q->update([
                    'urutan' => $newUrutan,
                    'kode_unsur' => 'U' . $newUrutan
                ]);
            }

            DB::commit();

            Log::info('Question deleted successfully', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Pertanyaan berhasil dihapus',
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to delete question', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus pertanyaan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reorder pertanyaan (drag & drop)
     */
    public function reorder(Request $request)
    {
        Log::info('SurveyQuestionController@reorder called', ['request' => $request->all()]);

        $validated = $request->validate([
            'orders' => 'required|array',
            'orders.*.id' => 'required|exists:survey_questions,id',
            'orders.*.urutan' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {
            foreach ($validated['orders'] as $order) {
                SurveyQuestion::where('id', $order['id'])
                    ->update([
                        'urutan' => $order['urutan'],
                        'kode_unsur' => 'U' . $order['urutan'] // Update kode_unsur based on new urutan
                    ]);
            }

            DB::commit();

            Log::info('Questions reordered successfully', [
                'count' => count($validated['orders'])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Urutan pertanyaan berhasil diperbarui',
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to reorder questions', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah urutan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tambah pilihan jawaban
     */
    public function storeOption(Request $request)
    {
        Log::info('SurveyQuestionController@storeOption called', ['request' => $request->all()]);

        $validated = $request->validate([
            'survey_question_id' => 'required|exists:survey_questions,id',
            'jawaban_text' => 'required|string|max:255',
            'poin' => 'required|integer|min:1|max:5',
        ]);

        DB::beginTransaction();

        try {
            // Get next urutan
            $maxUrutan = SurveyQuestionOption::where('survey_question_id', $validated['survey_question_id'])
                ->max('urutan') ?? 0;

            $option = SurveyQuestionOption::create([
                'survey_question_id' => $validated['survey_question_id'],
                'jawaban_text' => $validated['jawaban_text'],
                'poin' => $validated['poin'],
                'urutan' => $maxUrutan + 1,
            ]);

            DB::commit();

            Log::info('Option created successfully', [
                'id' => $option->id,
                'question_id' => $validated['survey_question_id']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pilihan jawaban berhasil ditambahkan',
                'data' => $option,
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to create option', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan pilihan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update pilihan jawaban
     */
    public function updateOption(Request $request, $id)
    {
        Log::info('SurveyQuestionController@updateOption called', [
            'id' => $id,
            'request' => $request->all()
        ]);

        $validated = $request->validate([
            'jawaban_text' => 'required|string|max:255',
            'poin' => 'required|integer|min:1|max:5',
        ]);

        try {
            $option = SurveyQuestionOption::findOrFail($id);

            $option->update([
                'jawaban_text' => $validated['jawaban_text'],
                'poin' => $validated['poin'],
            ]);

            Log::info('Option updated successfully', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Pilihan jawaban berhasil diperbarui',
                'data' => $option->fresh(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update option', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui pilihan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Hapus pilihan jawaban
     */
    public function destroyOption($id)
    {
        Log::info('SurveyQuestionController@destroyOption called', ['id' => $id]);

        DB::beginTransaction();

        try {
            $option = SurveyQuestionOption::findOrFail($id);
            $questionId = $option->survey_question_id;
            $deletedUrutan = $option->urutan;

            // Cek apakah option ini sudah digunakan di survey_responses
            $usageCount = $option->responses()->count();
            if ($usageCount > 0) {
                Log::warning('Attempted to delete option with responses', [
                    'id' => $id,
                    'response_count' => $usageCount
                ]);

                return response()->json([
                    'success' => false,
                    'message' => "Pilihan tidak bisa dihapus karena sudah digunakan oleh {$usageCount} jawaban survey.",
                ], 422);
            }

            // Hapus option
            $option->delete();

            // Reorder urutan option lain
            SurveyQuestionOption::where('survey_question_id', $questionId)
                ->where('urutan', '>', $deletedUrutan)
                ->decrement('urutan');

            DB::commit();

            Log::info('Option deleted successfully', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Pilihan jawaban berhasil dihapus',
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to delete option', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus pilihan: ' . $e->getMessage(),
            ], 500);
        }
    }
}
