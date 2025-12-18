<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\SurveyTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDF;
use Carbon\Carbon;

class SurveyController extends Controller
{
    /**
     * 📊 Menampilkan daftar survey (untuk admin panel) dengan filter waktu
     */
    public function index(Request $request)
    {
        $filter = $request->query('filter', 'all');
        $date   = $request->query('date', null);

        $query = Survey::query();

        switch ($filter) {
            case 'today':
                $query->whereDate('created_at', now()->toDateString());
                break;
            case 'custom':
                if ($date) $query->whereDate('created_at', $date);
                break;
            case 'all':
            default:
                break;
        }

        $surveys = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.survey.index', compact('surveys', 'filter', 'date'));
    }

    /**
     * 🧾 Menyimpan data survey dari API (Flutter/React)
     * Mendukung 2 format:
     * 1. Format lama (legacy): jawaban = array of {jawaban, nilai}
     * 2. Format baru (template): survey_template_id + responses = array of {question_id, option_id/text_answer, poin}
     */
public function store(Request $request)
{
    Log::info('SurveyController@store called', ['request' => $request->all()]);

    // Detect format: new (with survey_template_id) vs old (with jawaban array)
    $isNewFormat = $request->has('survey_template_id') || $request->has('responses');

    if ($isNewFormat) {
        // NEW FORMAT VALIDATION
        $validated = $request->validate([
            'survey_template_id' => 'required|integer|exists:survey_templates,id',
            'antrian_id'       => 'nullable|integer|exists:antrian,id',
            'nomor_antrian'    => 'nullable|string|max:10',
            'layanan_publik_id' => 'nullable|integer|exists:layanan_publik,id',
            'nama_responden'   => 'required|string|max:255',
            'no_hp_wa'         => 'nullable|string|max:20',
            'usia'             => 'required|integer|min:1|max:120',
            'jenis_kelamin'    => 'required|in:Laki-laki,Perempuan',
            'pendidikan'       => 'required|string|max:100',
            'pekerjaan'        => 'required|string|max:100',
            'bidang'           => 'nullable|string|max:150',
            'tanggal'          => 'nullable|date',
            'responses'        => 'required|array|min:1',
            'responses.*.question_id' => 'required|integer|exists:survey_questions,id',
            'responses.*.option_id'   => 'nullable|integer|exists:survey_question_options,id',
            'responses.*.text_answer' => 'nullable|string',
            'responses.*.poin'        => 'nullable|integer|min:1|max:5',
            'saran'            => 'nullable|string',
        ]);

        return $this->storeNewFormat($validated);

    } else {
        // OLD FORMAT VALIDATION (backward compatibility)
        $validated = $request->validate([
            'antrian_id'       => 'nullable|integer|exists:antrian,id',
            'nomor_antrian'    => 'nullable|string|max:10',
            'layanan_publik_id' => 'nullable|integer|exists:layanan_publik,id',
            'nama_responden'   => 'required|string|max:255',
            'no_hp_wa'         => 'nullable|string|max:20',
            'usia'             => 'required|integer|min:1|max:120',
            'jenis_kelamin'    => 'required|in:Laki-laki,Perempuan',
            'pendidikan'       => 'required|string|max:100',
            'pekerjaan'        => 'required|string|max:100',
            'bidang'           => 'nullable|string|max:150',
            'tanggal'          => 'nullable|date',
            'jawaban'          => 'required|array',
            'jawaban.*.jawaban' => 'required|string',
            'jawaban.*.nilai'   => 'required|integer|min:0|max:4',
            'saran'            => 'nullable|string',
        ]);

        return $this->storeOldFormat($validated);
    }
}

/**
 * Store survey dengan format lama (legacy)
 */
private function storeOldFormat($validated)
{
    // ✅ Check for duplicate survey for layanan_publik
    if (!empty($validated['layanan_publik_id'])) {
        $existingSurvey = Survey::where('layanan_publik_id', $validated['layanan_publik_id'])->first();

        if ($existingSurvey) {
            return response()->json([
                'success' => false,
                'message' => 'Survey untuk layanan ini sudah pernah diisi.',
                'data' => $existingSurvey,
            ], 422);
        }
    }

    // ✅ Auto-populate from LayananPublik if layanan_publik_id is provided
    if (!empty($validated['layanan_publik_id'])) {
        $layanan = \App\Models\LayananPublik::find($validated['layanan_publik_id']);

        if ($layanan) {
            $validated['nama_responden'] = $validated['nama_responden'] ?? $layanan->nama;
            $validated['bidang'] = $validated['bidang'] ?? $layanan->bidang;
            $validated['no_hp_wa'] = $validated['no_hp_wa'] ?? $layanan->telepon;
        }
    }

    // ✅ Auto-lookup antrian_id from nomor_antrian
    $antrianId = $validated['antrian_id'] ?? null;

    if (!$antrianId && !empty($validated['nomor_antrian'])) {
        $antrian = \App\Models\Antrian::where('nomor_antrian', $validated['nomor_antrian'])
            ->whereDate('tanggal_layanan', now('Asia/Makassar'))
            ->first();

        if ($antrian) {
            $antrianId = $antrian->id;
        }
    }

    $survey = Survey::create([
        'survey_template_id' => null, // Old format doesn't use template
        'antrian_id'       => $antrianId,
        'nomor_antrian'    => $validated['nomor_antrian'] ?? null,
        'layanan_publik_id' => $validated['layanan_publik_id'] ?? null,
        'nama_responden'   => $validated['nama_responden'],
        'no_hp_wa'         => $validated['no_hp_wa'] ?? null,
        'usia'             => $validated['usia'],
        'jenis_kelamin'    => $validated['jenis_kelamin'],
        'pendidikan'       => $validated['pendidikan'],
        'pekerjaan'        => $validated['pekerjaan'],
        'bidang'           => $validated['bidang'] ?? null,
        'tanggal'          => $validated['tanggal'] ?? now(),
        'jawaban'          => $validated['jawaban'], // Legacy JSON format
        'saran'            => $validated['saran'] ?? null,
        'surveyed_at'      => now(),
    ]);

    Log::info('Survey stored with old format', ['survey_id' => $survey->id]);

    return response()->json([
        'success' => true,
        'message' => 'Survey berhasil disimpan.',
        'data'    => $survey,
    ], 201);
}

/**
 * Store survey dengan format baru (template-based)
 */
private function storeNewFormat($validated)
{
    DB::beginTransaction();

    try {
        // ✅ Check for duplicate survey for layanan_publik
        if (!empty($validated['layanan_publik_id'])) {
            $existingSurvey = Survey::where('layanan_publik_id', $validated['layanan_publik_id'])->first();

            if ($existingSurvey) {
                return response()->json([
                    'success' => false,
                    'message' => 'Survey untuk layanan ini sudah pernah diisi.',
                    'data' => $existingSurvey,
                ], 422);
            }
        }

        // ✅ Auto-populate from LayananPublik if layanan_publik_id is provided
        if (!empty($validated['layanan_publik_id'])) {
            $layanan = \App\Models\LayananPublik::find($validated['layanan_publik_id']);

            if ($layanan) {
                $validated['nama_responden'] = $validated['nama_responden'] ?? $layanan->nama;
                $validated['bidang'] = $validated['bidang'] ?? $layanan->bidang;
                $validated['no_hp_wa'] = $validated['no_hp_wa'] ?? $layanan->telepon;
            }
        }

        // ✅ Auto-lookup antrian_id from nomor_antrian
        $antrianId = $validated['antrian_id'] ?? null;

        if (!$antrianId && !empty($validated['nomor_antrian'])) {
            $antrian = \App\Models\Antrian::where('nomor_antrian', $validated['nomor_antrian'])
                ->whereDate('tanggal_layanan', now('Asia/Makassar'))
                ->first();

            if ($antrian) {
                $antrianId = $antrian->id;
            }
        }

        // Create survey record
        $survey = Survey::create([
            'survey_template_id' => $validated['survey_template_id'],
            'antrian_id'       => $antrianId,
            'nomor_antrian'    => $validated['nomor_antrian'] ?? null,
            'layanan_publik_id' => $validated['layanan_publik_id'] ?? null,
            'nama_responden'   => $validated['nama_responden'],
            'no_hp_wa'         => $validated['no_hp_wa'] ?? null,
            'usia'             => $validated['usia'],
            'jenis_kelamin'    => $validated['jenis_kelamin'],
            'pendidikan'       => $validated['pendidikan'],
            'pekerjaan'        => $validated['pekerjaan'],
            'bidang'           => $validated['bidang'] ?? null,
            'tanggal'          => $validated['tanggal'] ?? now(),
            'jawaban'          => null, // New format stores in survey_responses
            'saran'            => $validated['saran'] ?? null,
            'surveyed_at'      => now(),
        ]);

        // Store each response
        foreach ($validated['responses'] as $response) {
            SurveyResponse::create([
                'survey_id' => $survey->id,
                'survey_question_id' => $response['question_id'],
                'survey_option_id' => $response['option_id'] ?? null,
                'jawaban_text' => $response['text_answer'] ?? ($response['option_id'] ? \App\Models\SurveyQuestionOption::find($response['option_id'])->jawaban_text : null),
                'poin' => $response['poin'] ?? null,
            ]);
        }

        DB::commit();

        Log::info('Survey stored with new format', [
            'survey_id' => $survey->id,
            'template_id' => $validated['survey_template_id'],
            'responses_count' => count($validated['responses'])
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Survey berhasil disimpan.',
            'data'    => $survey->load('responses'),
        ], 201);

    } catch (\Exception $e) {
        DB::rollback();

        Log::error('Failed to store survey with new format', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Gagal menyimpan survey: ' . $e->getMessage(),
        ], 500);
    }
}


    /**
     * 🔍 Menampilkan detail survey tertentu (untuk admin)
     */
    public function show($id)
    {
        $survey = Survey::findOrFail($id);

        // Decode jawaban JSON agar bisa ditampilkan
        $jawaban = $survey->jawaban ? json_decode($survey->jawaban, true) : [];

        return view('admin.survey.show', compact('survey', 'jawaban'));
    }

    /**
     * ❌ Menghapus survey tertentu
     */
public function destroy($id)
{
    try {
        $survey = Survey::findOrFail($id);
        $survey->delete();

        return response()->json(['success' => true]);
    } catch (\Exception $e) {
        \Log::error('Gagal hapus survey: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Gagal menghapus survey'
        ], 500);
    }
}


    /**
     * 📄 Download daftar survey PDF sesuai filter waktu
     */
public function downloadPdf(Request $request)
{
    $filter = $request->query('filter', 'all');
    $date   = $request->query('date', null);

    $query = Survey::query();

    switch ($filter) {
        case 'today':
            $query->whereDate('tanggal', now()->toDateString());
            $dateText = now()->format('d-m-Y'); // ⬅️ ganti slash ke dash
            break;
        case 'all':
            $dateText = 'Semua';
            break;
        case 'custom':
            if ($date) $query->whereDate('tanggal', $date);
            $dateText = $date ? \Carbon\Carbon::parse($date)->format('d-m-Y') : '-';
            break;
        default:
            $dateText = '-';
            break;
    }

    $surveys = $query->orderBy('tanggal', 'desc')->get();

    // Nama file PDF valid tanpa slash
    $filename = "Daftar Survey - {$dateText}.pdf";

    return PDF::loadView('admin.exports.survey_pdf', [
        'surveys' => $surveys,
        'dateText' => $dateText
    ])
    ->setPaper('a4', 'portrait')
    ->stream($filename);
}

}
