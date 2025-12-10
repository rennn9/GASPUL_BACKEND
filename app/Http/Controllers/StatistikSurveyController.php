<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Survey;
use App\Exports\SurveyRespondenExport;
use Maatwebsite\Excel\Facades\Excel;

class StatistikSurveyController extends Controller
{
    public function index(Request $request)
    {
        // ============================================
        // 1. Ambil tanggal default
        // ============================================
        $defaultAwal  = Survey::min('tanggal');
        $defaultAkhir = Survey::max('tanggal');

        $awal  = $request->awal ?: $defaultAwal;
        $akhir = $request->akhir ?: $defaultAkhir;

        // ============================================
        // 2. Query berdasarkan periode
        // ============================================
        $surveys = Survey::whereBetween('tanggal', [$awal, $akhir])->get();

        // ============================================
        // 3. Mapping pertanyaan ke U1–U9
        // ============================================
        $mapping = [
            "Bagaimana pendapat Saudara tentang kesesuaian persyaratan pelayanan dengan jenis pelayanannya?" => "U1",
            "Bagaimana pemahaman Saudara tentang kemudahan prosedur pelayanan di unit ini?" => "U2",
            "Bagaimana pendapat Saudara tentang kecepatan waktu dalam memberikan pelayanan?" => "U3",
            "Bagaimana pendapat Saudara tentang kewajaran biaya/tarif dalam pelayanan?" => "U4",
            "Bagaimana pendapat Saudara tentang kesesuaian produk pelayanan antara yang tercantum dalam standar pelayanan dengan hasil yang diberikan?" => "U5",
            "Bagaimana pendapat Saudara tentang kompetensi/kemampuan petugas dalam pelayanan?" => "U6",
            "Bagaimana pendapat Saudara tentang perilaku petugas dalam pelayanan terkait kesopanan dan keramahan?" => "U7",
            "Bagaimana pendapat Saudara tentang kualitas sarana dan prasarana?" => "U8",
            "Bagaimana pendapat Saudara tentang penanganan pengaduan pengguna layanan?" => "U9",
        ];

        $respondenData = [];
        $unsurNilai = [];
        $validIndex = 0;

        // ============================================
        // 4. Ambil nilai responden + DROP nilai 0 atau tidak lengkap
        // ============================================
        foreach ($surveys as $survey) {

$jawaban = $survey->jawaban;

// Decode pertama
$jawaban = is_string($jawaban) ? json_decode($jawaban, true) : $jawaban;

// Kalau masih string → decode kedua
if (is_string($jawaban)) {
    $jawaban = json_decode($jawaban, true);
}

// Jika tetap bukan array → skip
if (!is_array($jawaban)) {
    continue;
}

            $tempResponden = [];
            $dropResponden = false;

            foreach ($mapping as $pertanyaan => $kodeUnsur) {

                if (!isset($jawaban[$pertanyaan]['nilai'])) {
                    $dropResponden = true;
                    break;
                }

                $nilai = floatval($jawaban[$pertanyaan]['nilai']);

                if ($nilai == 0) {
                    $dropResponden = true;
                    break;
                }

                $tempResponden[$kodeUnsur] = $nilai;
            }

            // Drop jika ada nilai 0 atau unsur tidak lengkap
            if ($dropResponden || count($tempResponden) < 9) {
                continue;
            }

            // Responden valid → simpan dengan index berurutan
            $respondenData[$validIndex] = $tempResponden;

            foreach ($tempResponden as $kode => $nilai) {
                $unsurNilai[$kode][] = $nilai;
            }

            $validIndex++;
        }

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

        return Excel::download(
            new SurveyRespondenExport($awal, $akhir),
            "IKM_Survey_{$awal}_{$akhir}.xlsx"
        );
    }
}
