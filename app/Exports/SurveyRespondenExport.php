<?php

namespace App\Exports;

use App\Models\Survey;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class SurveyRespondenExport implements WithEvents
{
    protected $awal;
    protected $akhir;

    public function __construct($awal = null, $akhir = null)
    {
        // ekspektasi format 'Y-m-d' atau null
        $this->awal = $awal;
        $this->akhir = $akhir;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                // worksheet delegate (PhpSpreadsheet worksheet)
                $sheet = $event->sheet->getDelegate();

                // -----------------------
                // Ambil data survey (sesuai periode jika diberikan)
                // -----------------------
                $query = Survey::query();
                if (!empty($this->awal) && !empty($this->akhir)) {
                    // include hari akhir
                    $query->whereBetween('tanggal', [$this->awal, $this->akhir]);
                }
                $surveys = $query->orderBy('tanggal')->get();

                // Mapping pertanyaan -> U1..U9
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

                $unsurLabels = [
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

                $unsurKeys = array_keys($unsurLabels);

                // -----------------------
                // Build respondenData: setiap elemen => assoc [U1=>nilai,...U9=>nilai]
                // -----------------------
                $respondenData = [];
                foreach ($surveys as $sIndex => $s) {
                    $jawabanRaw = $s->jawaban;

                    // jawaban bisa sudah array (cast) atau string JSON
                    $jawaban = is_string($jawabanRaw) ? json_decode($jawabanRaw, true) : $jawabanRaw;
                    if (!is_array($jawaban)) {
                        // jika tidak valid, isi kosong
                        $res = [];
                        foreach ($unsurKeys as $k) $res[$k] = 0;
                        $respondenData[] = $res;
                        continue;
                    }

                    // default 0 untuk tiap unsur
                    $res = array_fill_keys($unsurKeys, 0);

                    foreach ($jawaban as $pertanyaan => $data) {
                        // pastikan struktur {jawaban:..., nilai:...}
                        if (!is_array($data)) continue;
                        if (!isset($data['nilai'])) continue;
                        $key = $mapping[$pertanyaan] ?? null;
                        if (!$key) continue;
                        $nilai = floatval($data['nilai'] ?? 0);
                        // hanya ambil nilai > 0, tapi kalau 0 tetap simpan 0
                        $res[$key] = $nilai;
                    }

                    $respondenData[] = $res;
                }

                $totalResponden = count($respondenData);

                // -----------------------
                // Hitung jumlah & rata-rata per unsur
                // -----------------------
                $jumlahPerUnsur = [];
                $rataPerUnsur = [];
                foreach ($unsurKeys as $k) {
                    $sum = 0;
                    foreach ($respondenData as $r) {
                        $sum += ($r[$k] ?? 0);
                    }
                    $jumlahPerUnsur[$k] = $sum;
                    $rataPerUnsur[$k] = $totalResponden > 0 ? round($sum / $totalResponden, 2) : 0;
                }

                // Rata-rata tertimbang (avg * 25) per unsur
                $tertimbangPerUnsur = [];
                foreach ($unsurKeys as $k) {
                    $tertimbangPerUnsur[$k] = round($rataPerUnsur[$k] * 25, 2);
                }

                // Jumlah rata-rata tertimbang (penjumlahan semua tertimbang)
                $jumlahRataTertimbang = array_sum($tertimbangPerUnsur);

                // IKM unit pelayanan: rata dari rataPerUnsur * 25
                $ikmUnit = count($unsurKeys) > 0 ? round(array_sum($rataPerUnsur) / count($unsurKeys) * 25, 2) : 0;

                // -----------------------
                // BUILD RESPONDEN SUMMARY (untuk tabel Info Responden)
                // -----------------------
                $total = $surveys->count();
                // standar nama gender di DB mungkin berbeda, support beberapa variasi
                $laki = $surveys->filter(function ($s) {
                    return in_array(strtolower($s->jenis_kelamin), ['laki-laki','laki','male']);
                })->count();
                $perempuan = $surveys->filter(function ($s) {
                    return in_array(strtolower($s->jenis_kelamin), ['perempuan','perempuan','female']);
                })->count();

                $pendidikanCounts = $surveys->groupBy(function ($s) {
                    return $s->pendidikan ?? 'LAINNYA';
                })->map->count()->toArray();

                // usia groups dynamic
                $minUsia = $surveys->pluck('usia')->filter()->min();
                $maxUsia = $surveys->pluck('usia')->filter()->max();
                $interval = 10;
                $usiaGroups = [];
                if ($minUsia !== null && $maxUsia !== null) {
                    $start = floor($minUsia / $interval) * $interval;
                    for ($s = $start; $s <= $maxUsia; $s += $interval) {
                        $e = $s + $interval - 1;
                        $count = $surveys->filter(function ($it) use ($s, $e) {
                            if ($it->usia === null) return false;
                            return $it->usia >= $s && $it->usia <= $e;
                        })->count();
                        $usiaGroups[] = ['range' => "{$s} - {$e}", 'count' => $count];
                    }
                }

                // periode
                $periodeText = '-';
                if ($this->awal && $this->akhir) {
                    $periodeText = "{$this->awal} s/d {$this->akhir}";
                } elseif ($surveys->count() > 0) {
                    $periodeText = $surveys->first()->tanggal?->format('Y-m-d') . " s/d " . $surveys->last()->tanggal?->format('Y-m-d');
                }

                // -----------------------
                // Mulai menulis ke sheet
                // -----------------------
                $row = 1;

                // Judul
                $sheet->setCellValue("A{$row}", 'LAPORAN SURVEY - IKM');
                $sheet->mergeCells("A{$row}:K{$row}");
                $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(14);
                $row += 2;

                // Periode & ringkasan singkat
                $sheet->setCellValue("A{$row}", "Periode: {$periodeText}");
                $sheet->mergeCells("A{$row}:K{$row}");
                $sheet->getStyle("A{$row}")->getFont()->setItalic(true);
                $row += 2;

                /*
                 * INFO RESPONDEN (BARU)
                 */
                $sheet->setCellValue("A{$row}", 'Info Responden');
                $sheet->mergeCells("A{$row}:K{$row}");
                $sheet->getStyle("A{$row}")->getFont()->setBold(true);
                $row++;

                // Total Responden
                $sheet->setCellValue("A{$row}", 'Total Responden');
                $sheet->setCellValue("B{$row}", $total);
                $row++;

                // Jenis Kelamin
                $sheet->setCellValue("A{$row}", 'Jenis Kelamin - Laki-Laki');
                $sheet->setCellValue("B{$row}", $laki);
                $row++;
                $sheet->setCellValue("A{$row}", 'Jenis Kelamin - Perempuan');
                $sheet->setCellValue("B{$row}", $perempuan);
                $row++;

                // Pendidikan (tulis tiap kategori di baris terpisah)
                $sheet->setCellValue("A{$row}", 'Pendidikan');
                $sheet->getStyle("A{$row}")->getFont()->setBold(true);
                $row++;
                if (!empty($pendidikanCounts)) {
                    foreach ($pendidikanCounts as $pend => $cnt) {
                        $sheet->setCellValue("A{$row}", "- " . strtoupper($pend));
                        $sheet->setCellValue("B{$row}", $cnt);
                        $row++;
                    }
                } else {
                    $sheet->setCellValue("A{$row}", '-');
                    $row++;
                }

                // Usia (interval)
                $sheet->setCellValue("A{$row}", 'Usia (Interval)');
                $sheet->getStyle("A{$row}")->getFont()->setBold(true);
                $row++;
                if (!empty($usiaGroups)) {
                    foreach ($usiaGroups as $g) {
                        $sheet->setCellValue("A{$row}", "- " . $g['range'] . " tahun");
                        $sheet->setCellValue("B{$row}", $g['count']);
                        $row++;
                    }
                } else {
                    $sheet->setCellValue("A{$row}", '-');
                    $row++;
                }

                // Periode (ulangi)
                $sheet->setCellValue("A{$row}", 'Periode Survey');
                $sheet->setCellValue("B{$row}", $periodeText);
                $row += 2;

                /*
                 * 1) TABEL Perhitungan IKM per Responden
                 */
                $sheet->setCellValue("A{$row}", 'Tabel Perhitungan IKM per Responden');
                $sheet->getStyle("A{$row}")->getFont()->setBold(true);
                $row += 1;

                // header
                $colMap = range('A', 'Z');
                $startColIndex = 0; // A
                $sheet->setCellValue($colMap[$startColIndex] . $row, 'Responden');
                for ($i = 0; $i < count($unsurKeys); $i++) {
                    $sheet->setCellValue($colMap[$startColIndex + 1 + $i] . $row, $unsurKeys[$i]);
                }
                // bold header
                $sheet->getStyle($colMap[$startColIndex] . $row . ":" . $colMap[$startColIndex + count($unsurKeys)] . $row)
                      ->getFont()->setBold(true);

                // body: tiap responden
                $row++;
                foreach ($respondenData as $i => $r) {
                    $sheet->setCellValue($colMap[$startColIndex] . $row, 'Responden ' . ($i + 1));
                    for ($j = 0; $j < count($unsurKeys); $j++) {
                        $k = $unsurKeys[$j];
                        $sheet->setCellValue($colMap[$startColIndex + 1 + $j] . $row, $r[$k]);
                    }
                    $row++;
                }

                // simpan posisi footer tabel responden
                $respondenFooterRow = $row;

                // footer baris: Jumlah, Rata-rata, Rata-rata tertimbang, Jumlah rata-rata tertimbang, IKM
                // Jumlah Nilai / Unsur
                $sheet->setCellValue($colMap[$startColIndex] . $row, 'Jumlah Nilai / Unsur');
                for ($j = 0; $j < count($unsurKeys); $j++) {
                    $k = $unsurKeys[$j];
                    $sheet->setCellValue($colMap[$startColIndex + 1 + $j] . $row, $jumlahPerUnsur[$k]);
                }
                $sheet->getStyle($colMap[$startColIndex] . $row . ":" . $colMap[$startColIndex + count($unsurKeys)] . $row)
                      ->getFont()->setBold(true);
                $row++;

                // Rata-Rata / Unsur
                $sheet->setCellValue($colMap[$startColIndex] . $row, 'Rata-Rata / Unsur');
                for ($j = 0; $j < count($unsurKeys); $j++) {
                    $k = $unsurKeys[$j];
                    $sheet->setCellValue($colMap[$startColIndex + 1 + $j] . $row, $rataPerUnsur[$k]);
                }
                $sheet->getStyle($colMap[$startColIndex] . $row . ":" . $colMap[$startColIndex + count($unsurKeys)] . $row)
                      ->getFont()->setBold(true);
                $row++;

                // Rata-Rata Tertimbang / Unsur
                $sheet->setCellValue($colMap[$startColIndex] . $row, 'Rata-Rata Tertimbang / Unsur (×25)');
                for ($j = 0; $j < count($unsurKeys); $j++) {
                    $k = $unsurKeys[$j];
                    $sheet->setCellValue($colMap[$startColIndex + 1 + $j] . $row, $tertimbangPerUnsur[$k]);
                }
                $sheet->getStyle($colMap[$startColIndex] . $row . ":" . $colMap[$startColIndex + count($unsurKeys)] . $row)
                      ->getFont()->setBold(true);
                $row++;

                // Jumlah Rata-Rata Tertimbang (memanjang)
                $sheet->setCellValue($colMap[$startColIndex] . $row, 'Jumlah Rata-Rata Tertimbang');
                $sheet->setCellValue($colMap[$startColIndex + 1] . $row, $jumlahRataTertimbang);
                // merge agar memanjang sampai kolom terakhir
                $sheet->mergeCells($colMap[$startColIndex + 1] . $row . ":" . $colMap[$startColIndex + count($unsurKeys)] . $row);
                $sheet->getStyle($colMap[$startColIndex] . $row . ":" . $colMap[$startColIndex + count($unsurKeys)] . $row)
                      ->getFont()->setBold(true);
                $row++;

                // IKM Unit Pelayanan (memanjang)
                $sheet->setCellValue($colMap[$startColIndex] . $row, 'IKM Unit Pelayanan');
                $sheet->setCellValue($colMap[$startColIndex + 1] . $row, $ikmUnit);
                $sheet->mergeCells($colMap[$startColIndex + 1] . $row . ":" . $colMap[$startColIndex + count($unsurKeys)] . $row);
                // set bold + background fill for the IKM row
                $sheet->getStyle($colMap[$startColIndex] . $row . ":" . $colMap[$startColIndex + count($unsurKeys)] . $row)
                      ->getFont()->setBold(true);
                $sheet->getStyle($colMap[$startColIndex] . $row . ":" . $colMap[$startColIndex + count($unsurKeys)] . $row)
                      ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                      ->getStartColor()->setARGB('FFFFF2CC'); // soft background
                $row += 2;

                /*
                 * 2) TABEL Nilai Rata-rata Per Unsur (ringkasan)
                 */
                $sheet->setCellValue("A{$row}", 'Nilai Rata-rata Per Unsur (Ringkasan)');
                $sheet->getStyle("A{$row}")->getFont()->setBold(true);
                $row += 1;

                $sheet->setCellValue("A{$row}", 'Kode');
                $sheet->setCellValue("B{$row}", 'Unsur Pelayanan');
                $sheet->setCellValue("C{$row}", 'Rata-Rata');
                $sheet->getStyle("A{$row}:C{$row}")->getFont()->setBold(true);
                $row++;

                foreach ($unsurKeys as $k) {
                    $sheet->setCellValue("A{$row}", $k);
                    $sheet->setCellValue("B{$row}", $unsurLabels[$k]);
                    $sheet->setCellValue("C{$row}", $rataPerUnsur[$k]);
                    $row++;
                }
                $row += 1;

                /*
                 * 3) Keterangan
                 */
                $sheet->setCellValue("A{$row}", 'Keterangan');
                $sheet->getStyle("A{$row}")->getFont()->setBold(true);
                $row += 1;

                $kets = [
                    "U1 – U9 = Unsur-Unsur Pelayanan",
                    "Nilai Rata-Rata per Unsur = (Jumlah seluruh nilai unsur) ÷ (Jumlah responden)",
                    "Nilai Rata-Rata Tertimbang = Nilai Rata-Rata × 25 (konversi ke skala 0–100)",
                    "IKM Unit Pelayanan = (Jumlah rata-rata unsur ÷ jumlah unsur) × 25"
                ];
                foreach ($kets as $kline) {
                    $sheet->setCellValue("A{$row}", "- {$kline}");
                    $row++;
                }
                $row += 1;

                /*
                 * 4) Mutu Pelayanan
                 */
                $sheet->setCellValue("A{$row}", 'Mutu Pelayanan');
                $sheet->getStyle("A{$row}")->getFont()->setBold(true);
                $row += 1;

                $sheet->setCellValue("A{$row}", 'Mutu');
                $sheet->setCellValue("B{$row}", 'Rentang Nilai');
                $sheet->getStyle("A{$row}:B{$row}")->getFont()->setBold(true);
                $row++;

                $mutus = [
                    ['A (Sangat Baik)', '88.31 - 100.00'],
                    ['B (Baik)', '76.61 - 88.30'],
                    ['C (Kurang Baik)', '65.00 - 76.60'],
                    ['D (Tidak Baik)', '25.00 - 64.99'],
                ];
                foreach ($mutus as $m) {
                    $sheet->setCellValue("A{$row}", $m[0]);
                    $sheet->setCellValue("B{$row}", $m[1]);
                    $row++;
                }

                // Auto size columns A..K (kasar)
                foreach (range('A', 'K') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            }, // AfterSheet handler end
        ];
    }
}
