<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Survey;
use App\Models\SurveyTemplate;
use App\Exports\SurveyRespondenExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class StatistikSurveyController extends Controller
{
    public function index(Request $request)
    {
        Log::info('StatistikSurveyController@index called', ['request' => $request->all()]);

        // ============================================
        // 1. Ambil semua templates untuk dropdown
        // ============================================
        $allTemplates = SurveyTemplate::orderBy('created_at', 'desc')->get();

        // ============================================
        // 2. Tentukan template yang dipilih (filter)
        // ============================================
        $selectedTemplateId = $request->template_id;

        // Jika tidak ada filter template, gunakan yang aktif
        if (!$selectedTemplateId) {
            $activeTemplate = SurveyTemplate::where('is_active', true)->first();
            $selectedTemplateId = $activeTemplate?->id;
        }

        // Ambil template yang dipilih untuk mendapatkan pertanyaan
        $selectedTemplate = null;
        $templateQuestions = collect();

        if ($selectedTemplateId) {
            $selectedTemplate = SurveyTemplate::with('questions.options')->find($selectedTemplateId);
            if ($selectedTemplate) {
                $templateQuestions = $selectedTemplate->questions()
                    ->where('kode_unsur', '!=', null)
                    ->orderBy('urutan')
                    ->get();
            }
        }

        // ============================================
        // 3. Ambil tanggal default
        // ============================================
        $defaultAwal  = Survey::min('tanggal');
        $defaultAkhir = Survey::max('tanggal');

        $awal  = $request->awal ?: $defaultAwal;
        $akhir = $request->akhir ?: $defaultAkhir;

        // ============================================
        // 4. Query berdasarkan periode DAN template
        // ============================================
        $surveysQuery = Survey::with(['template', 'responses.question', 'responses.option'])
            ->whereBetween('tanggal', [$awal, $akhir]);

        // Filter hanya survey yang menggunakan template yang dipilih
        if ($selectedTemplateId) {
            $surveysQuery->where('survey_template_id', $selectedTemplateId);
        } else {
            // Jika tidak ada template dipilih, ambil legacy surveys
            $surveysQuery->whereNull('survey_template_id');
        }

        $surveys = $surveysQuery->get();

        Log::info('Surveys loaded', [
            'count' => $surveys->count(),
            'template_id' => $selectedTemplateId,
            'selected_template' => $selectedTemplate?->nama
        ]);

        // ============================================
        // 5. Build dynamic unsur mapping dari template
        // ============================================
        $unsurMapping = [];  // kode_unsur => label
        $expectedUnsurCount = 0;

        if ($templateQuestions->count() > 0) {
            // Template-based: gunakan pertanyaan dari template
            foreach ($templateQuestions as $question) {
                // Use pertanyaan if available, otherwise use kode_unsur as label
                $unsurMapping[$question->kode_unsur] = $question->pertanyaan ?: $question->kode_unsur;
            }
            $expectedUnsurCount = $templateQuestions->count();
        } else {
            // Legacy: gunakan hardcoded mapping
            $unsurMapping = [
                'U1' => 'Persyaratan pelayanan',
                'U2' => 'Prosedur pelayanan',
                'U3' => 'Waktu pelayanan',
                'U4' => 'Biaya / tarif pelayanan',
                'U5' => 'Produk pelayanan',
                'U6' => 'Kompetensi petugas pelayanan',
                'U7' => 'Perilaku petugas pelayanan',
                'U8' => 'Sarana dan prasarana',
                'U9' => 'Penanganan pengaduan layanan',
            ];
            $expectedUnsurCount = 9;
        }

        Log::info('Unsur mapping built', [
            'count' => count($unsurMapping),
            'unsur' => array_keys($unsurMapping)
        ]);

        $respondenData = [];
        $unsurNilai = [];
        $validIndex = 0;

        // ============================================
        // 6. Ambil nilai responden + DROP nilai 0 atau tidak lengkap
        // ============================================
        foreach ($surveys as $survey) {
            $tempResponden = [];

            // === HYBRID: Template ID exists but data in OLD FORMAT (jawaban column) ===
            // Check jawaban FIRST before responses table (priority for legacy data)
            if ($survey->survey_template_id && $survey->jawaban) {
                Log::debug('Processing template-based survey (old format - jawaban column)', ['survey_id' => $survey->id]);

                // jawaban is already an array (auto-cast by Laravel)
                $jawaban = $survey->jawaban;

                // Handle if jawaban is still a STRING (double-encoded JSON)
                if (is_string($jawaban)) {
                    $jawaban = json_decode($jawaban, true);
                }

                if (!is_array($jawaban)) {
                    Log::debug('Skipping survey: jawaban not array', [
                        'survey_id' => $survey->id,
                        'jawaban_type' => gettype($survey->jawaban)
                    ]);
                    continue;
                }

                // Detect jawaban format and extract nilai
                $isIndexedArray = isset($jawaban[0]); // Format: ["Sangat sesuai", "Mudah", ...]
                $isObjectFormat = !$isIndexedArray && isset(array_values($jawaban)[0]['nilai']); // Format: {"pertanyaan": {"jawaban": "...", "nilai": 4}}

                if ($isObjectFormat) {
                    // Format: {"pertanyaan": {"jawaban": "...", "nilai": 4}}
                    $index = 0;
                    foreach ($jawaban as $pertanyaan => $data) {
                        if (isset($data['nilai']) && $data['nilai'] > 0) {
                            $kodeUnsur = 'U' . ($index + 1);
                            if (isset($unsurMapping[$kodeUnsur])) {
                                $tempResponden[$kodeUnsur] = floatval($data['nilai']);
                            }
                            $index++;
                        }
                    }
                } elseif ($isIndexedArray) {
                    // Format: ["Sangat sesuai", "Mudah", ...] - need to map to nilai
                    // Map jawaban text to nilai (hardcoded for now)
                    $nilaiMapping = [
                        'Sangat sesuai' => 4, 'Sesuai' => 3, 'Kurang sesuai' => 2, 'Tidak sesuai' => 1,
                        'Sangat mudah' => 4, 'Mudah' => 3, 'Kurang mudah' => 2, 'Tidak mudah' => 1,
                        'Sangat cepat' => 4, 'Cepat' => 3, 'Kurang cepat' => 2, 'Tidak cepat' => 1,
                        'Gratis' => 4, 'Murah' => 3, 'Cukup mahal' => 2, 'Sangat mahal' => 1,
                        'Sangat kompeten' => 4, 'Kompeten' => 3, 'Kurang kompeten' => 2, 'Tidak kompeten' => 1,
                        'Sangat sopan dan ramah' => 4, 'Sopan dan ramah' => 3, 'Kurang sopan dan ramah' => 2, 'Tidak sopan dan ramah' => 1,
                        'Sangat Baik' => 4, 'Baik' => 3, 'Cukup' => 2, 'Buruk' => 1,
                        'Dikelola dengan baik' => 4, 'Berfungsi kurang maksimal' => 3, 'Ada tetapi tidak berfungsi' => 2, 'Tidak ada' => 1,
                    ];

                    foreach ($jawaban as $index => $jawabanText) {
                        $kodeUnsur = 'U' . ($index + 1);
                        if (isset($unsurMapping[$kodeUnsur]) && isset($nilaiMapping[$jawabanText])) {
                            $tempResponden[$kodeUnsur] = floatval($nilaiMapping[$jawabanText]);
                        }
                    }
                } else {
                    // Format: {"U1": 4, "U2": 3, ...} - direct mapping
                    foreach ($unsurMapping as $kodeUnsur => $label) {
                        if (isset($jawaban[$kodeUnsur])) {
                            $nilai = $jawaban[$kodeUnsur];

                            // Skip nilai 0 atau null
                            if ($nilai === null || $nilai == 0) {
                                continue;
                            }

                            $tempResponden[$kodeUnsur] = floatval($nilai);
                        }
                    }
                }

                // Drop jika tidak lengkap
                if (count($tempResponden) < $expectedUnsurCount) {
                    Log::debug('Dropping survey: incomplete jawaban', [
                        'survey_id' => $survey->id,
                        'count' => count($tempResponden),
                        'expected' => $expectedUnsurCount
                    ]);
                    continue;
                }
            }
            // === Template-based surveys with NEW FORMAT (survey_responses table) ===
            elseif ($survey->survey_template_id && $survey->responses->count() > 0) {
                Log::debug('Processing template-based survey (new format)', ['survey_id' => $survey->id]);

                foreach ($survey->responses as $response) {
                    $kodeUnsur = $response->question->kode_unsur;
                    $nilai = $response->poin;

                    // Skip jika tidak ada kode unsur atau poin null/0
                    if (!$kodeUnsur || $nilai === null || $nilai == 0) {
                        continue;
                    }

                    // Hanya ambil unsur yang ada di mapping (dari template yang dipilih)
                    if (isset($unsurMapping[$kodeUnsur])) {
                        $tempResponden[$kodeUnsur] = floatval($nilai);
                    }
                }

                // Drop jika tidak lengkap
                if (count($tempResponden) < $expectedUnsurCount) {
                    Log::debug('Dropping survey: incomplete responses', [
                        'survey_id' => $survey->id,
                        'count' => count($tempResponden),
                        'expected' => $expectedUnsurCount
                    ]);
                    continue;
                }
            }
            else {
                // === Skip: No template_id or no data ===
                Log::debug('Skipping survey: no template or no data', [
                    'survey_id' => $survey->id,
                    'has_template' => !!$survey->survey_template_id,
                    'responses_count' => $survey->responses->count(),
                    'has_jawaban' => !!$survey->jawaban
                ]);
                continue;
            }

            // Responden valid → simpan dengan index berurutan
            $respondenData[$validIndex] = $tempResponden;

            foreach ($tempResponden as $kode => $nilai) {
                $unsurNilai[$kode][] = $nilai;
            }

            $validIndex++;
        }

        Log::info('Valid respondents processed', ['count' => $validIndex]);

        // Hitung ulang total responden valid
        $totalResponden = count($respondenData);

        // Jika tidak ada responden valid
        if ($totalResponden == 0) {
            return view('admin.statistik.survey', [
                'rataPerUnsur'             => [],
                'jumlahPerUnsur'           => [],
                'rata2PerUnsur'            => [],
                'rataTertimbangPerUnsur'   => [],
                'totalWeighted'            => 0,
                'ikmTotal'                 => 0,
                'mutuTotal'                => 'Belum Ada Data',
                'totalResponden'           => 0,
                'respondenData'            => [],
                'surveys'                  => collect(),
                'periodeAwal'              => $awal,
                'periodeAkhir'             => $akhir,
                // Template data
                'allTemplates'             => $allTemplates,
                'selectedTemplateId'       => $selectedTemplateId,
                'selectedTemplate'         => $selectedTemplate,
                'unsurMapping'             => $unsurMapping,
            ]);
        }

        // ============================================
        // 5. Hitung rata-rata per unsur
        // ============================================
        $rataPerUnsur = [];
        foreach ($unsurNilai as $u => $nilaiArray) {
            $rataPerUnsur[$u] = round(array_sum($nilaiArray) / count($nilaiArray), 2);
        }

        // ============================================
        // 6. Hitung tabel per unsur
        // ============================================
        $jumlahPerUnsur = [];
        $rata2PerUnsur = [];
        $rataTertimbangPerUnsur = [];
        $totalWeighted = 0;

        foreach ($rataPerUnsur as $u => $avg) {
            $jumlahPerUnsur[$u] = round($avg * $totalResponden, 2);
            $rata2PerUnsur[$u] = round($avg, 2);
            $rataTertimbangPerUnsur[$u] = round($avg * 25, 2);
            $totalWeighted += ($avg * 25);
        }

        $totalWeighted = round($totalWeighted, 2);

        // ============================================
        // 7. Hitung IKM Unit
        // ============================================
        $sumAvg = array_sum($rataPerUnsur);
        $ikmUnit = round(($sumAvg / count($rataPerUnsur)) * 25, 2);

        // ============================================
        // 8. Tentukan kategori mutu
        // ============================================
        $kategori = match (true) {
            $ikmUnit >= 88.31 => 'A (Sangat Baik)',
            $ikmUnit >= 76.61 => 'B (Baik)',
            $ikmUnit >= 65.00 => 'C (Cukup)',
            default => 'D (Kurang)',
        };

        // ============================================
        // 9. Return ke view
        // ============================================
        return view('admin.statistik.survey', [
            'rataPerUnsur'               => $rataPerUnsur,
            'jumlahPerUnsur'             => $jumlahPerUnsur,
            'rata2PerUnsur'              => $rata2PerUnsur,
            'rataTertimbangPerUnsur'     => $rataTertimbangPerUnsur,
            'totalWeighted'              => $totalWeighted,
            'ikmTotal'                   => $ikmUnit,
            'mutuTotal'                  => $kategori,
            'totalResponden'             => $totalResponden,
            'respondenData'              => $respondenData,
            'surveys'                    => $surveys,
            'periodeAwal'                => $awal,
            'periodeAkhir'               => $akhir,
            // Template data
            'allTemplates'               => $allTemplates,
            'selectedTemplateId'         => $selectedTemplateId,
            'selectedTemplate'           => $selectedTemplate,
            'unsurMapping'               => $unsurMapping,
        ]);
    }

    public function resetPeriode()
    {
        return redirect()->route('admin.statistik.survey')
            ->with([
                'periodeAwal' => null,
                'periodeAkhir' => null,
            ]);
    }

    public function downloadExcel(Request $request)
    {
        $awal  = $request->awal ?: Survey::min('tanggal');
        $akhir = $request->akhir ?: Survey::max('tanggal');
        $templateId = $request->template_id;

        // Build filename with template info
        $filename = "IKM_Survey";

        if ($templateId) {
            $template = SurveyTemplate::find($templateId);
            if ($template) {
                $templateSlug = str_replace(' ', '_', $template->nama);
                $filename .= "_{$templateSlug}_v{$template->versi}";
            }
        }

        $filename .= "_{$awal}_{$akhir}.xlsx";

        return Excel::download(
            new SurveyRespondenExport($awal, $akhir, $templateId, auth()->user()),
            $filename
        );
    }
}
