<?php

namespace App\Http\Controllers;

use App\Models\SurveyTemplate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SurveyTemplateController extends Controller
{
    /**
     * List semua template survey (Web Admin View)
     */
    public function index(Request $request)
    {
        Log::info('SurveyTemplateController@index called');

        // Check if this is an API request
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->apiIndex();
        }

        $templates = SurveyTemplate::with('createdBy')
            ->orderBy('versi', 'desc')
            ->paginate(10);

        Log::info('Survey templates retrieved', ['count' => $templates->count()]);

        return view('admin.survey-template.index', compact('templates'));
    }

    /**
     * API: Get all templates (JSON)
     */
    public function apiIndex()
    {
        Log::info('SurveyTemplateController@apiIndex called (API)');

        $templates = SurveyTemplate::orderBy('versi', 'desc')->get();

        Log::info('API: Survey templates retrieved', ['count' => $templates->count()]);

        return response()->json([
            'success' => true,
            'data' => $templates,
        ]);
    }

    /**
     * API: Get active template with questions and options (JSON)
     */
    public function getActiveTemplate()
    {
        Log::info('SurveyTemplateController@getActiveTemplate called (API)');

        $template = SurveyTemplate::with(['questions' => function($query) {
                $query->orderBy('urutan');
            }, 'questions.options' => function($query) {
                $query->orderBy('urutan');
            }])
            ->where('is_active', true)
            ->first();

        if (!$template) {
            Log::warning('No active template found');
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada template survey yang aktif',
                'data' => null,
            ], 404);
        }

        Log::info('Active template found', [
            'id' => $template->id,
            'nama' => $template->nama,
            'questions_count' => $template->questions->count()
        ]);

        return response()->json([
            'success' => true,
            'data' => $template,
        ]);
    }

    /**
     * API: Get specific template by ID with questions and options (JSON)
     */
    public function show(Request $request, $id)
    {
        Log::info('SurveyTemplateController@show called', ['id' => $id]);

        // Check if this is an API request
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->apiShow($id);
        }

        // Web view logic here if needed
        $template = SurveyTemplate::findOrFail($id);
        return view('admin.survey-template.show', compact('template'));
    }

    /**
     * API: Get specific template (JSON)
     */
    private function apiShow($id)
    {
        $template = SurveyTemplate::with(['questions' => function($query) {
                $query->orderBy('urutan');
            }, 'questions.options' => function($query) {
                $query->orderBy('urutan');
            }])
            ->find($id);

        if (!$template) {
            Log::warning('Template not found', ['id' => $id]);
            return response()->json([
                'success' => false,
                'message' => 'Template tidak ditemukan',
                'data' => null,
            ], 404);
        }

        Log::info('Template found', [
            'id' => $template->id,
            'nama' => $template->nama,
            'questions_count' => $template->questions->count()
        ]);

        return response()->json([
            'success' => true,
            'data' => $template,
        ]);
    }

    /**
     * Form tambah template baru
     */
    public function create()
    {
        Log::info('SurveyTemplateController@create called');

        return view('admin.survey-template.create');
    }

    /**
     * Simpan template baru
     */
    public function store(Request $request)
    {
        Log::info('SurveyTemplateController@store called', ['request' => $request->all()]);

        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            // Get next versi number
            $latestVersion = SurveyTemplate::max('versi') ?? 0;
            $nextVersion = $latestVersion + 1;

            Log::info('Creating new template', ['versi' => $nextVersion]);

            // Get authenticated user ID and validate it exists in database
            $userId = auth()->id();

            Log::info('Auth user ID', ['auth_id' => $userId]);

            // Validate user exists in database, if not use fallback
            if ($userId) {
                $userExists = \App\Models\User::where('id', $userId)->exists();
                if (!$userExists) {
                    Log::warning('Auth user ID does not exist in database', ['invalid_id' => $userId]);
                    $userId = null;
                }
            }

            // Fallback to first admin user if auth ID is invalid
            if (!$userId) {
                $adminUser = \App\Models\User::whereIn('role', ['superadmin', 'admin'])->first();
                $userId = $adminUser ? $adminUser->id : null;
                Log::info('Using fallback admin user', ['fallback_id' => $userId]);
            }

            // Final validation: userId must not be null
            if (!$userId) {
                throw new \Exception('No valid user ID found. Please ensure you are logged in or at least one admin user exists.');
            }

            $template = SurveyTemplate::create([
                'nama' => $validated['nama'],
                'deskripsi' => $validated['deskripsi'] ?? null,
                'versi' => $nextVersion,
                'is_active' => false, // Default tidak aktif
                'created_by_user_id' => $userId,
            ]);

            DB::commit();

            Log::info('Template created successfully', ['id' => $template->id, 'versi' => $template->versi]);

            return redirect()
                ->route('admin.survey-questions.index', $template->id)
                ->with('success', 'Template berhasil dibuat. Silakan tambahkan pertanyaan.');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to create template', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal membuat template: ' . $e->getMessage());
        }
    }

    /**
     * Form edit template (hanya metadata: nama, deskripsi)
     */
    public function edit(SurveyTemplate $surveyTemplate)
    {
        Log::info('SurveyTemplateController@edit called', ['id' => $surveyTemplate->id]);

        return view('admin.survey-template.edit', compact('surveyTemplate'));
    }

    /**
     * Update template metadata
     */
    public function update(Request $request, SurveyTemplate $surveyTemplate)
    {
        Log::info('SurveyTemplateController@update called', [
            'id' => $surveyTemplate->id,
            'request' => $request->all()
        ]);

        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
        ]);

        try {
            $surveyTemplate->update([
                'nama' => $validated['nama'],
                'deskripsi' => $validated['deskripsi'] ?? null,
            ]);

            Log::info('Template updated successfully', ['id' => $surveyTemplate->id]);

            return redirect()
                ->route('admin.survey-templates.index')
                ->with('success', 'Template berhasil diperbarui.');

        } catch (\Exception $e) {
            Log::error('Failed to update template', [
                'id' => $surveyTemplate->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal memperbarui template: ' . $e->getMessage());
        }
    }

    /**
     * Soft delete template (set is_active = false)
     */
    public function destroy(SurveyTemplate $surveyTemplate)
    {
        Log::info('SurveyTemplateController@destroy called', ['id' => $surveyTemplate->id]);

        try {
            // Cek apakah template ini sedang aktif
            if ($surveyTemplate->is_active) {
                Log::warning('Attempted to delete active template', ['id' => $surveyTemplate->id]);
                return redirect()
                    ->back()
                    ->with('error', 'Template yang sedang aktif tidak bisa dihapus. Nonaktifkan terlebih dahulu.');
            }

            // Cek apakah template sudah digunakan oleh survey
            $usageCount = $surveyTemplate->surveys()->count();
            if ($usageCount > 0) {
                Log::warning('Attempted to delete template with surveys', [
                    'id' => $surveyTemplate->id,
                    'survey_count' => $usageCount
                ]);

                return redirect()
                    ->back()
                    ->with('error', "Template tidak bisa dihapus karena sudah digunakan oleh {$usageCount} survey.");
            }

            // Soft delete dengan cascade ke questions, options
            $surveyTemplate->delete();

            Log::info('Template deleted successfully', ['id' => $surveyTemplate->id]);

            return redirect()
                ->route('admin.survey-templates.index')
                ->with('success', 'Template berhasil dihapus.');

        } catch (\Exception $e) {
            Log::error('Failed to delete template', [
                'id' => $surveyTemplate->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Gagal menghapus template: ' . $e->getMessage());
        }
    }

    /**
     * Aktifkan template (hanya 1 yang bisa aktif)
     */
    public function activate(SurveyTemplate $surveyTemplate)
    {
        Log::info('SurveyTemplateController@activate called', ['id' => $surveyTemplate->id]);

        DB::beginTransaction();

        try {
            // Validasi: template harus punya minimal 1 pertanyaan
            $questionCount = $surveyTemplate->questions()->count();
            if ($questionCount === 0) {
                Log::warning('Attempted to activate template without questions', ['id' => $surveyTemplate->id]);
                return redirect()
                    ->back()
                    ->with('error', 'Template harus memiliki minimal 1 pertanyaan untuk diaktifkan.');
            }

            // Nonaktifkan semua template lain
            SurveyTemplate::where('is_active', true)->update(['is_active' => false]);
            Log::info('Deactivated all other templates');

            // Aktifkan template ini
            $surveyTemplate->update(['is_active' => true]);
            Log::info('Template activated', ['id' => $surveyTemplate->id]);

            DB::commit();

            return redirect()
                ->route('admin.survey-templates.index')
                ->with('success', 'Template berhasil diaktifkan.');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to activate template', [
                'id' => $surveyTemplate->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Gagal mengaktifkan template: ' . $e->getMessage());
        }
    }

    /**
     * Duplikasi template untuk edit
     */
    public function duplicate(SurveyTemplate $surveyTemplate)
    {
        Log::info('SurveyTemplateController@duplicate called', ['id' => $surveyTemplate->id]);

        DB::beginTransaction();

        try {
            // Get next versi number
            $latestVersion = SurveyTemplate::max('versi') ?? 0;
            $nextVersion = $latestVersion + 1;

            Log::info('Duplicating template', [
                'source_id' => $surveyTemplate->id,
                'new_versi' => $nextVersion
            ]);

            // Get authenticated user ID and validate it exists in database
            $userId = auth()->id();

            // Validate user exists in database, if not use fallback
            if ($userId) {
                $userExists = \App\Models\User::where('id', $userId)->exists();
                if (!$userExists) {
                    Log::warning('Auth user ID does not exist in database', ['invalid_id' => $userId]);
                    $userId = null;
                }
            }

            // Fallback to first admin user if auth ID is invalid
            if (!$userId) {
                $adminUser = \App\Models\User::whereIn('role', ['superadmin', 'admin'])->first();
                $userId = $adminUser ? $adminUser->id : null;
                Log::info('Using fallback admin user for duplicate', ['fallback_id' => $userId]);
            }

            // Final validation
            if (!$userId) {
                throw new \Exception('No valid user ID found. Please ensure you are logged in or at least one admin user exists.');
            }

            // Duplikasi template
            $newTemplate = SurveyTemplate::create([
                'nama' => $surveyTemplate->nama . ' (Copy)',
                'deskripsi' => $surveyTemplate->deskripsi,
                'versi' => $nextVersion,
                'is_active' => false,
                'created_by_user_id' => $userId,
            ]);

            // Duplikasi questions
            foreach ($surveyTemplate->questions as $question) {
                $newQuestion = $newTemplate->questions()->create([
                    'pertanyaan_text' => $question->pertanyaan_text,
                    'unsur_pelayanan' => $question->unsur_pelayanan,
                    'kode_unsur' => $question->kode_unsur,
                    'urutan' => $question->urutan,
                    'is_required' => $question->is_required,
                    'is_text_input' => $question->is_text_input,
                ]);

                // Duplikasi options
                foreach ($question->options as $option) {
                    $newQuestion->options()->create([
                        'jawaban_text' => $option->jawaban_text,
                        'poin' => $option->poin,
                        'urutan' => $option->urutan,
                    ]);
                }
            }

            DB::commit();

            Log::info('Template duplicated successfully', [
                'source_id' => $surveyTemplate->id,
                'new_id' => $newTemplate->id,
                'questions_copied' => $surveyTemplate->questions->count()
            ]);

            return redirect()
                ->route('admin.survey-questions.index', $newTemplate->id)
                ->with('success', 'Template berhasil diduplikasi. Anda bisa mengedit pertanyaan sekarang.');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to duplicate template', [
                'id' => $surveyTemplate->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Gagal menduplikasi template: ' . $e->getMessage());
        }
    }

    /**
     * Preview template dengan pertanyaan
     */
    public function preview(SurveyTemplate $surveyTemplate)
    {
        Log::info('SurveyTemplateController@preview called', ['id' => $surveyTemplate->id]);

        $template = $surveyTemplate->load('questions.options');

        return view('admin.survey-template.preview', compact('template'));
    }
}
