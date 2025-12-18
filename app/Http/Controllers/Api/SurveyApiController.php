<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SurveyTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SurveyApiController extends Controller
{
    /**
     * GET /api/survey/questions
     *
     * Fetch survey questions dari template aktif atau template tertentu
     *
     * Query params:
     * - template_id (optional): ID template spesifik
     *
     * Response:
     * {
     *   "success": true,
     *   "data": {
     *     "template": {...},
     *     "questions": [...]
     *   }
     * }
     */
    public function getQuestions(Request $request)
    {
        Log::info('API SurveyApiController@getQuestions called', ['request' => $request->all()]);

        try {
            $templateId = $request->query('template_id');

            if ($templateId) {
                // Get specific template
                $template = SurveyTemplate::with(['questions' => function($query) {
                    $query->orderBy('urutan');
                }, 'questions.options' => function($query) {
                    $query->orderBy('urutan');
                }])->findOrFail($templateId);

                Log::info('Template found by ID', ['template_id' => $templateId]);
            } else {
                // Get active template
                $template = SurveyTemplate::where('is_active', true)
                    ->with(['questions' => function($query) {
                        $query->orderBy('urutan');
                    }, 'questions.options' => function($query) {
                        $query->orderBy('urutan');
                    }])
                    ->first();

                if (!$template) {
                    Log::warning('No active template found');
                    return response()->json([
                        'success' => false,
                        'message' => 'Tidak ada template survey aktif saat ini.',
                        'data' => null
                    ], 404);
                }

                Log::info('Active template found', ['template_id' => $template->id]);
            }

            // Format response
            $response = [
                'success' => true,
                'data' => [
                    'template' => [
                        'id' => $template->id,
                        'nama' => $template->nama,
                        'versi' => $template->versi,
                        'deskripsi' => $template->deskripsi,
                    ],
                    'questions' => $template->questions->map(function($question) {
                        return [
                            'id' => $question->id,
                            'pertanyaan' => $question->pertanyaan_text,
                            'kode_unsur' => $question->kode_unsur,
                            'urutan' => $question->urutan,
                            'is_required' => $question->is_required,
                            'is_text_input' => $question->is_text_input,
                            'options' => $question->is_text_input ? [] : $question->options->map(function($option) {
                                return [
                                    'id' => $option->id,
                                    'jawaban' => $option->jawaban_text,
                                    'poin' => $option->poin,
                                    'urutan' => $option->urutan,
                                ];
                            })->values(),
                        ];
                    })->values(),
                ]
            ];

            Log::info('Survey questions returned successfully', [
                'template_id' => $template->id,
                'question_count' => $template->questions->count()
            ]);

            return response()->json($response);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Template not found', [
                'template_id' => $request->query('template_id'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Template survey tidak ditemukan.',
                'data' => null
            ], 404);

        } catch (\Exception $e) {
            Log::error('Error fetching survey questions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data survey.',
                'data' => null
            ], 500);
        }
    }
}
