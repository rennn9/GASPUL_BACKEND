<?php

namespace App\Exports;

use App\Models\Survey;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SurveyRespondenExport implements FromCollection, WithHeadings
{
    protected $awal;
    protected $akhir;

    public function __construct($awal = null, $akhir = null)
    {
        $this->awal = $awal;
        $this->akhir = $akhir;
    }

    public function collection()
    {
        $query = Survey::query();

        if ($this->awal && $this->akhir) {
            $query->whereBetween('tanggal', [$this->awal, $this->akhir]);
        }

        $surveys = $query->get();

        // Mapping jawaban ke kolom
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

        $rows = [];

        foreach ($surveys as $i => $survey) {
            $jawaban = is_string($survey->jawaban) ? json_decode($survey->jawaban, true) : $survey->jawaban;
            $row = [
                'Responden' => $survey->nama_responden,
                'Tanggal' => $survey->tanggal,
            ];

            foreach ($mapping as $pertanyaan => $key) {
                $row[$key] = $jawaban[$pertanyaan]['nilai'] ?? null;
            }

            $rows[] = $row;
        }

        return collect($rows);
    }

    public function headings(): array
    {
        return ['Responden', 'Tanggal', 'U1', 'U2', 'U3', 'U4', 'U5', 'U6', 'U7', 'U8', 'U9'];
    }
}
