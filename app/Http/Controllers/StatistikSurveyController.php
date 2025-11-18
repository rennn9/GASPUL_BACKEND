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
        // 1. Ambil tanggal default dari database
        // ============================================
        $defaultAwal  = Survey::min('tanggal');
        $defaultAkhir = Survey::max('tanggal');

        // ============================================
        // 2. Ambil tanggal dari filter (jika ada)
        // ============================================
$awal  = $request->has('awal')  && $request->awal !== ''  ? $request->awal  : $defaultAwal;
$akhir = $request->has('akhir') && $request->akhir !== '' ? $request->akhir : $defaultAkhir;



        // ============================================
        // 3. Query berdasarkan periode
        // ============================================
        $surveys = Survey::whereBetween('tanggal', [$awal, $akhir])->get();
        $totalResponden = $surveys->count();

if ($totalResponden === 0) {
    return view('admin.statistik.survey', [
        'rataPerUnsur'    => [],
        'ikmTotal'        => 0,
        'mutuTotal'       => 'Belum Ada Data',
        'totalResponden'  => 0,
        'respondenData'   => [],
        'surveys'         => collect(), // 🟢 ubah jadi Collection kosong
        'periodeAwal'     => $awal,
        'periodeAkhir'    => $akhir,
    ]);
}

        // ============================================
        // 4. Mapping pertanyaan ke U1–U9
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

        $unsurNilai = [];
        $respondenData = [];

        // ============================================
        // 5. Ambil nilai per responden per unsur
        // ============================================
        foreach ($surveys as $i => $survey) {

            $jawaban = is_string($survey->jawaban)
                ? json_decode($survey->jawaban, true)
                : $survey->jawaban;

            if (!is_array($jawaban)) continue;

            foreach ($jawaban as $pertanyaan => $data) {

                if (!isset($data['nilai']) || $data['nilai'] <= 0) continue;

                $key = $mapping[$pertanyaan] ?? null;
                if (!$key) continue;

                $nilai = floatval($data['nilai']);

                // Nilai setiap responden (untuk tabel)
                $respondenData[$i][$key] = $nilai;

                // Nilai global
                $unsurNilai[$key][] = $nilai;
            }
        }

        // ============================================
        // 6. Hitung rata-rata U1–U9
        // ============================================
        $rataPerUnsur = [];
        foreach ($unsurNilai as $u => $nilaiArray) {
            $rataPerUnsur[$u] = round(array_sum($nilaiArray) / count($nilaiArray), 2);
        }

        // ============================================
        // 7. Hitung IKM
        // ============================================
        $sumAvg = array_sum($rataPerUnsur);
        $ikmUnit = count($rataPerUnsur) > 0
            ? round(($sumAvg / count($rataPerUnsur)) * 25, 2)
            : 0;

        // ============================================
        // 8. Tentukan Mutu
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
            'rataPerUnsur'    => $rataPerUnsur,
            'ikmTotal'        => $ikmUnit,
            'mutuTotal'       => $kategori,
            'totalResponden'  => $totalResponden,
            'respondenData'   => $respondenData,
            'surveys'         => $surveys,
            'periodeAwal'     => $awal,
            'periodeAkhir'    => $akhir,
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
