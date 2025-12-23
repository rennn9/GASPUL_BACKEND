<?php

namespace App\Exports;

use App\Models\Survey;
use App\Models\SurveyTemplate;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Events\AfterSheet;

class SurveyRespondenExport implements WithMultipleSheets
{
    protected $awal;
    protected $akhir;
    protected $templateId;
    protected $user;

    public function __construct($awal = null, $akhir = null, $templateId = null, $user = null)
    {
        // ekspektasi format 'Y-m-d' atau null
        $this->awal = $awal;
        $this->akhir = $akhir;
        $this->templateId = $templateId;
        $this->user = $user;
    }

    public function sheets(): array
    {
        return [
            new SurveyRespondenSheet1($this->awal, $this->akhir, $this->templateId, $this->user),
            new SurveyRespondenSheet2($this->awal, $this->akhir, $this->templateId, $this->user),
        ];
    }
}

// ============================================================================
// SHEET 1: Data Lengkap (Format Existing)
// ============================================================================
class SurveyRespondenSheet1 implements WithEvents
{
    protected $awal;
    protected $akhir;
    protected $templateId;
    protected $user;

    public function __construct($awal = null, $akhir = null, $templateId = null, $user = null)
    {
        $this->awal = $awal;
        $this->akhir = $akhir;
        $this->templateId = $templateId;
        $this->user = $user;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                // worksheet delegate (PhpSpreadsheet worksheet)
                $sheet = $event->sheet->getDelegate();

                // -----------------------
                // Ambil data survey (sesuai periode dan template jika diberikan)
                // -----------------------
                $query = Survey::with(['template', 'responses.question', 'responses.option']);

                // Filter by period
                if (!empty($this->awal) && !empty($this->akhir)) {
                    $query->whereBetween('tanggal', [$this->awal, $this->akhir]);
                }

                // Filter by template
                if (!empty($this->templateId)) {
                    $query->where('survey_template_id', $this->templateId);
                } else {
                    // Legacy surveys without template
                    $query->whereNull('survey_template_id');
                }

                $surveys = $query->orderBy('tanggal')->get();

                // Get selected template for dynamic unsur mapping
                $selectedTemplate = null;
                $templateQuestions = collect();

                if ($this->templateId) {
                    $selectedTemplate = SurveyTemplate::with('questions.options')->find($this->templateId);
                    if ($selectedTemplate) {
                        $templateQuestions = $selectedTemplate->questions()
                            ->where('kode_unsur', '!=', null)
                            ->orderBy('urutan')
                            ->get();
                    }
                }

                // Build dynamic unsur mapping from template or use legacy
                $unsurMapping = [];
                $expectedUnsurCount = 0;

                if ($templateQuestions->count() > 0) {
                    // Template-based: use questions from template
                    foreach ($templateQuestions as $question) {
                        $unsurMapping[$question->kode_unsur] = $question->pertanyaan ?: $question->kode_unsur;
                    }
                    $expectedUnsurCount = $templateQuestions->count();
                } else {
                    // Legacy: use hardcoded mapping
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

                $unsurKeys = array_keys($unsurMapping);

                // -----------------------
                // Build respondenData: setiap elemen => assoc [U1=>nilai,...U9=>nilai]
                // Mendukung MULTI-FORMAT: Hybrid, Legacy, dan Template-based
                // Same logic as StatistikSurveyController
                // -----------------------
                $respondenData = [];
                $validIndex = 0;

                foreach ($surveys as $s) {
                    $tempResponden = [];

                    // === HYBRID: Template ID exists but data in OLD FORMAT (jawaban column) ===
                    // Check jawaban FIRST before responses table (priority for legacy data)
                    if ($s->survey_template_id && $s->jawaban) {
                        $jawaban = $s->jawaban;

                        // Handle if jawaban is still a STRING (double-encoded JSON)
                        if (is_string($jawaban)) {
                            $jawaban = json_decode($jawaban, true);
                        }

                        if (!is_array($jawaban)) {
                            continue;
                        }

                        // Detect jawaban format and extract nilai
                        $isIndexedArray = isset($jawaban[0]);
                        $isObjectFormat = !$isIndexedArray && isset(array_values($jawaban)[0]['nilai']);

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
                            // Format: ["Sangat sesuai", "Mudah", ...]
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
                            // Format: {"U1": 4, "U2": 3, ...}
                            foreach ($unsurMapping as $kodeUnsur => $label) {
                                if (isset($jawaban[$kodeUnsur])) {
                                    $nilai = $jawaban[$kodeUnsur];
                                    if ($nilai !== null && $nilai != 0) {
                                        $tempResponden[$kodeUnsur] = floatval($nilai);
                                    }
                                }
                            }
                        }

                        // Drop if incomplete
                        if (count($tempResponden) < $expectedUnsurCount) {
                            continue;
                        }
                    }
                    // === Template-based surveys with NEW FORMAT (survey_responses table) ===
                    elseif ($s->survey_template_id && $s->responses->count() > 0) {
                        foreach ($s->responses as $response) {
                            $kodeUnsur = $response->question->kode_unsur;
                            $nilai = $response->poin;

                            if (!$kodeUnsur || $nilai === null || $nilai == 0) {
                                continue;
                            }

                            if (isset($unsurMapping[$kodeUnsur])) {
                                $tempResponden[$kodeUnsur] = floatval($nilai);
                            }
                        }

                        // Drop if incomplete
                        if (count($tempResponden) < $expectedUnsurCount) {
                            continue;
                        }
                    }
                    else {
                        // Skip: No template_id or no data
                        continue;
                    }

                    // Responden valid → simpan
                    $respondenData[$validIndex] = $tempResponden;
                    $validIndex++;
                }

                $totalResponden = count($respondenData);

                // -----------------------
                // Calculate aggregates - will use Excel formulas instead of hardcoded values
                // Keep these for reference info section only
                // -----------------------
                $totalResponden = count($respondenData);

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

                // periode with Indonesian month names
                $periodeText = '-';
                if ($this->awal && $this->akhir) {
                    $awalDate = \Carbon\Carbon::parse($this->awal);
                    $akhirDate = \Carbon\Carbon::parse($this->akhir);
                    $periodeText = $awalDate->translatedFormat('d F Y') . " s/d " . $akhirDate->translatedFormat('d F Y');
                } elseif ($surveys->count() > 0) {
                    $periodeText = $surveys->first()->tanggal?->translatedFormat('d F Y') . " s/d " . $surveys->last()->tanggal?->translatedFormat('d F Y');
                }

                // -----------------------
                // Mulai menulis ke sheet
                // -----------------------
                $row = 1;

                // Define column mapping
                $colMap = range('A', 'Z');

                // Calculate merge range based on unsur count
                $lastCol = $colMap[count($unsurKeys)]; // Dynamic based on unsur count

                // Judul - Row 1
                $sheet->setCellValue("B{$row}", 'LAPORAN INDEKS KEPUASAN MASYARAKAT (IKM)');
                $sheet->mergeCells("B{$row}:{$lastCol}{$row}");
                $sheet->getStyle("B{$row}")->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle("B{$row}")->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                $sheet->getRowDimension($row)->setRowHeight(30);
                $row++;

                // Template info (if selected) - Row 2
                if ($selectedTemplate) {
                    $sheet->setCellValue("B{$row}", "Template: {$selectedTemplate->nama} (Versi {$selectedTemplate->versi})");
                    $sheet->mergeCells("B{$row}:{$lastCol}{$row}");
                    $sheet->getStyle("B{$row}")->getFont()->setBold(true)->setSize(12);
                    $sheet->getStyle("B{$row}")->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                    $sheet->getRowDimension($row)->setRowHeight(20);
                    $row++;
                }

                // Periode - Row 3 (or 2 if no template)
                $sheet->setCellValue("B{$row}", "Periode: {$periodeText}");
                $sheet->mergeCells("B{$row}:{$lastCol}{$row}");
                $sheet->getStyle("B{$row}")->getFont()->setItalic(true)->setSize(11);
                $sheet->getStyle("B{$row}")->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                $sheet->getRowDimension($row)->setRowHeight(18);
                $row++;

                // System origin text - Row 4 (or 3 if no template)
                $sheet->setCellValue("B{$row}", 'Dokumen ini dihasilkan dari Sistem GASPUL');
                $sheet->mergeCells("B{$row}:{$lastCol}{$row}");
                $sheet->getStyle("B{$row}")->getFont()->setItalic(true)->setSize(9)->getColor()->setARGB('FF666666');
                $sheet->getStyle("B{$row}")->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                $sheet->getRowDimension($row)->setRowHeight(16);
                $row++;

                // Download metadata (date and user) - Row 5 (or 4 if no template)
                $downloadDate = \Carbon\Carbon::now()->translatedFormat('d F Y, H:i');
                $downloadUser = $this->user ? $this->user->name : 'Unknown';
                $sheet->setCellValue("B{$row}", "Didownload pada: {$downloadDate} | Oleh: {$downloadUser}");
                $sheet->mergeCells("B{$row}:{$lastCol}{$row}");
                $sheet->getStyle("B{$row}")->getFont()->setItalic(true)->setSize(9)->getColor()->setARGB('FF666666');
                $sheet->getStyle("B{$row}")->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                $sheet->getRowDimension($row)->setRowHeight(16);

                // Add GASPUL logo - positioned to the left of all text (Column A, spans rows 1-5)
                $logoPath = public_path('assets/images/logo-gaspul.png');
                if (file_exists($logoPath)) {
                    $logoStartRow = 1;
                    $logoEndRow = $row;

                    $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                    $drawing->setName('GASPUL Logo');
                    $drawing->setDescription('GASPUL Logo');
                    $drawing->setPath($logoPath);
                    $drawing->setHeight(80);  // Logo height in pixels
                    $drawing->setCoordinates("A{$logoStartRow}");
                    $drawing->setOffsetX(10);
                    $drawing->setOffsetY(10);
                    $drawing->setWorksheet($sheet);

                    // Set column A width for logo
                    $sheet->getColumnDimension('A')->setWidth(15);
                }

                $row += 2;

                /*
                 * INFO RESPONDEN SECTION
                 */
                $sheet->setCellValue("A{$row}", 'INFORMASI RESPONDEN');
                $sheet->mergeCells("A{$row}:D{$row}");
                $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle("A{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFD9E1F2');
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
                 * TABEL Perhitungan IKM per Responden
                 */
                $sheet->setCellValue("A{$row}", 'TABEL PERHITUNGAN IKM PER RESPONDEN');
                $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
                $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle("A{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFD9E1F2');
                $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $row += 1;

                // Header with labels
                $startColIndex = 0; // A
                $headerRow = $row;

                $sheet->setCellValue($colMap[$startColIndex] . $row, 'RESPONDEN');
                for ($i = 0; $i < count($unsurKeys); $i++) {
                    $kode = $unsurKeys[$i];
                    $label = $unsurMapping[$kode];
                    // Write kode_unsur in header
                    $sheet->setCellValue($colMap[$startColIndex + 1 + $i] . $row, $kode);
                }

                // Style header: bold + background + centered
                $headerRange = $colMap[$startColIndex] . $row . ":" . $colMap[$startColIndex + count($unsurKeys)] . $row;
                $sheet->getStyle($headerRange)->getFont()->setBold(true);
                $sheet->getStyle($headerRange)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFE7E6E6');
                $sheet->getStyle($headerRange)->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle($headerRange)->getBorders()->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

                // Body: tiap responden
                $row++;
                $dataStartRow = $row; // Save for formulas

                foreach ($respondenData as $i => $r) {
                    $sheet->setCellValue($colMap[$startColIndex] . $row, 'Responden ' . ($i + 1));
                    for ($j = 0; $j < count($unsurKeys); $j++) {
                        $k = $unsurKeys[$j];
                        $val = $r[$k] ?? 0;
                        $sheet->setCellValue($colMap[$startColIndex + 1 + $j] . $row, $val);

                        // Center align numbers
                        $sheet->getStyle($colMap[$startColIndex + 1 + $j] . $row)
                            ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    }

                    // Add borders to data rows
                    $dataRange = $colMap[$startColIndex] . $row . ":" . $colMap[$startColIndex + count($unsurKeys)] . $row;
                    $sheet->getStyle($dataRange)->getBorders()->getAllBorders()
                        ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

                    $row++;
                }

                $dataEndRow = $row - 1; // Last row with actual data

                // ===== CALCULATED ROWS WITH EXCEL FORMULAS =====

                // Row 1: Jumlah Nilai / Unsur (SUM formula)
                $jumlahRow = $row;
                $sheet->setCellValue($colMap[$startColIndex] . $row, 'Jumlah Nilai / Unsur');
                for ($j = 0; $j < count($unsurKeys); $j++) {
                    $col = $colMap[$startColIndex + 1 + $j];
                    // SUM formula for each column
                    $formula = "=SUM({$col}{$dataStartRow}:{$col}{$dataEndRow})";
                    $sheet->setCellValue($col . $row, $formula);
                    $sheet->getStyle($col . $row)->getAlignment()
                        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                }
                $rowRange = $colMap[$startColIndex] . $row . ":" . $colMap[$startColIndex + count($unsurKeys)] . $row;
                $sheet->getStyle($rowRange)->getFont()->setBold(true);
                $sheet->getStyle($rowRange)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFFCE4D6');
                $sheet->getStyle($rowRange)->getBorders()->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $row++;

                // Row 2: NRR per Unsur (AVERAGE formula) - Nilai Rata-Rata
                $nrrRow = $row;
                $sheet->setCellValue($colMap[$startColIndex] . $row, 'NRR per Unsur (Nilai Rata-Rata)');
                for ($j = 0; $j < count($unsurKeys); $j++) {
                    $col = $colMap[$startColIndex + 1 + $j];
                    // AVERAGE formula
                    $formula = "=AVERAGE({$col}{$dataStartRow}:{$col}{$dataEndRow})";
                    $sheet->setCellValue($col . $row, $formula);
                    $sheet->getStyle($col . $row)->getNumberFormat()
                        ->setFormatCode('General'); // No trailing zeros, no comma
                    $sheet->getStyle($col . $row)->getAlignment()
                        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                }
                $rowRange = $colMap[$startColIndex] . $row . ":" . $colMap[$startColIndex + count($unsurKeys)] . $row;
                $sheet->getStyle($rowRange)->getFont()->setBold(true);
                $sheet->getStyle($rowRange)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFFCE4D6');
                $sheet->getStyle($rowRange)->getBorders()->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $row++;

                // Row 3: Bobot per Unsur (1 / jumlah unsur)
                $bobotRow = $row;
                $unsurCount = count($unsurKeys);
                $bobot = 1 / $unsurCount;
                $sheet->setCellValue($colMap[$startColIndex] . $row, 'Bobot per Unsur (1/' . $unsurCount . ')');
                for ($j = 0; $j < count($unsurKeys); $j++) {
                    $col = $colMap[$startColIndex + 1 + $j];
                    // Bobot formula: 1/jumlah unsur
                    $formula = "=1/{$unsurCount}";
                    $sheet->setCellValue($col . $row, $formula);
                    $sheet->getStyle($col . $row)->getNumberFormat()
                        ->setFormatCode('General'); // No trailing zeros, no comma
                    $sheet->getStyle($col . $row)->getAlignment()
                        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                }
                $rowRange = $colMap[$startColIndex] . $row . ":" . $colMap[$startColIndex + count($unsurKeys)] . $row;
                $sheet->getStyle($rowRange)->getFont()->setBold(true);
                $sheet->getStyle($rowRange)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFF4E8D6');
                $sheet->getStyle($rowRange)->getBorders()->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $row++;

                // Row 4: NRR Tertimbang (NRR × Bobot)
                $nrrTertimbangRow = $row;
                $sheet->setCellValue($colMap[$startColIndex] . $row, 'NRR Tertimbang (NRR × Bobot)');
                for ($j = 0; $j < count($unsurKeys); $j++) {
                    $col = $colMap[$startColIndex + 1 + $j];
                    // Formula: NRR × Bobot
                    $formula = "={$col}{$nrrRow}*{$col}{$bobotRow}";
                    $sheet->setCellValue($col . $row, $formula);
                    $sheet->getStyle($col . $row)->getNumberFormat()
                        ->setFormatCode('General'); // No trailing zeros, no comma
                    $sheet->getStyle($col . $row)->getAlignment()
                        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                }
                $rowRange = $colMap[$startColIndex] . $row . ":" . $colMap[$startColIndex + count($unsurKeys)] . $row;
                $sheet->getStyle($rowRange)->getFont()->setBold(true);
                $sheet->getStyle($rowRange)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFFCE4D6');
                $sheet->getStyle($rowRange)->getBorders()->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $row++;

                // Row 5: Total NRR Tertimbang (SUM of NRR Tertimbang)
                $totalNrrTertimbangRow = $row;
                $sheet->setCellValue($colMap[$startColIndex] . $row, 'Total NRR Tertimbang');
                $firstUnsurCol = $colMap[$startColIndex + 1];
                $lastUnsurCol = $colMap[$startColIndex + count($unsurKeys)];
                $formula = "=SUM({$firstUnsurCol}{$nrrTertimbangRow}:{$lastUnsurCol}{$nrrTertimbangRow})";
                $sheet->setCellValue($colMap[$startColIndex + 1] . $row, $formula);
                $sheet->getStyle($colMap[$startColIndex + 1] . $row)->getNumberFormat()
                    ->setFormatCode('General'); // No trailing zeros, no comma
                $sheet->mergeCells($colMap[$startColIndex + 1] . $row . ":" . $lastUnsurCol . $row);
                $rowRange = $colMap[$startColIndex] . $row . ":" . $lastUnsurCol . $row;
                $sheet->getStyle($rowRange)->getFont()->setBold(true)->setSize(11);
                $sheet->getStyle($rowRange)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFFFF2CC');
                $sheet->getStyle($rowRange)->getBorders()->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM);
                $sheet->getStyle($colMap[$startColIndex + 1] . $row)->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $row++;

                // Row 6: IKM Unit Pelayanan (Total NRR Tertimbang × 25)
                $ikmRow = $row;
                $sheet->setCellValue($colMap[$startColIndex] . $row, 'IKM UNIT PELAYANAN (× 25)');
                $formula = "={$firstUnsurCol}{$totalNrrTertimbangRow}*25";
                $sheet->setCellValue($colMap[$startColIndex + 1] . $row, $formula);
                $sheet->getStyle($colMap[$startColIndex + 1] . $row)->getNumberFormat()
                    ->setFormatCode('General'); // No trailing zeros, no comma
                $sheet->mergeCells($colMap[$startColIndex + 1] . $row . ":" . $lastUnsurCol . $row);
                $rowRange = $colMap[$startColIndex] . $row . ":" . $lastUnsurCol . $row;
                $sheet->getStyle($rowRange)->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle($rowRange)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFFFD966');
                $sheet->getStyle($rowRange)->getBorders()->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM);
                $sheet->getStyle($colMap[$startColIndex + 1] . $row)->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $row += 2;

                /*
                 * TABEL Nilai Rata-rata Per Unsur (ringkasan dengan label lengkap)
                 */
                $sheet->setCellValue("A{$row}", 'NILAI RATA-RATA PER UNSUR PELAYANAN');
                $sheet->mergeCells("A{$row}:E{$row}");
                $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle("A{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFD9E1F2');
                $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $row += 1;

                // Header
                $sheet->setCellValue("A{$row}", 'Kode');
                $sheet->setCellValue("B{$row}", 'Unsur Pelayanan');
                $sheet->setCellValue("C{$row}", 'NRR');
                $sheet->setCellValue("D{$row}", 'Bobot');
                $sheet->setCellValue("E{$row}", 'NRR Tertimbang');
                $sheet->getStyle("A{$row}:E{$row}")->getFont()->setBold(true);
                $sheet->getStyle("A{$row}:E{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFE7E6E6');
                $sheet->getStyle("A{$row}:E{$row}")->getBorders()->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $sheet->getStyle("A{$row}:E{$row}")->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $row++;

                // Data rows with formulas referencing main table
                foreach ($unsurKeys as $idx => $k) {
                    $colRef = $colMap[$startColIndex + 1 + $idx]; // Column in main table

                    $sheet->setCellValue("A{$row}", $k);
                    $sheet->setCellValue("B{$row}", $unsurMapping[$k]);

                    // Reference to NRR cell in main table
                    $sheet->setCellValue("C{$row}", "={$colRef}{$nrrRow}");
                    $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('General');

                    // Reference to Bobot cell in main table
                    $sheet->setCellValue("D{$row}", "={$colRef}{$bobotRow}");
                    $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('General');

                    // Reference to NRR Tertimbang cell in main table
                    $sheet->setCellValue("E{$row}", "={$colRef}{$nrrTertimbangRow}");
                    $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('General');

                    // Borders and alignment
                    $sheet->getStyle("A{$row}:E{$row}")->getBorders()->getAllBorders()
                        ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                    $sheet->getStyle("C{$row}:E{$row}")->getAlignment()
                        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                    $row++;
                }
                $row += 1;

                /*
                 * KETERANGAN PERHITUNGAN (Sesuai Permenpan RB No. 14 Tahun 2017)
                 */
                $sheet->setCellValue("A{$row}", 'KETERANGAN PERHITUNGAN (PERMENPAN RB NO. 14 TAHUN 2017)');
                $sheet->mergeCells("A{$row}:E{$row}");
                $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle("A{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFD9E1F2');
                $row += 1;

                // Helper untuk format angka tanpa trailing zeros
                $formatNumber = function($num, $maxDecimals = 3) {
                    // Gunakan sprintf untuk menghindari masalah locale
                    $formatted = sprintf("%.{$maxDecimals}f", $num);
                    $formatted = rtrim($formatted, '0');
                    $formatted = rtrim($formatted, '.');
                    return $formatted === '' ? '0' : $formatted;
                };

                $bobotValue = $formatNumber(1/$expectedUnsurCount);

                $kets = [
                    "Unsur Pelayanan yang dinilai = {$expectedUnsurCount} unsur (U1 – U{$expectedUnsurCount})",
                    "Jumlah Responden = {$totalResponden} orang",
                    "NRR per Unsur (Nilai Rata-Rata) = Σ Nilai per Unsur ÷ Jumlah Responden",
                    "Bobot per Unsur = 1 ÷ {$expectedUnsurCount} = {$bobotValue} (sama untuk semua unsur)",
                    "NRR Tertimbang = NRR per Unsur × Bobot",
                    "Total NRR Tertimbang = Σ (NRR Tertimbang semua unsur)",
                    "IKM Unit Pelayanan = Total NRR Tertimbang × 25 (konversi skala 1-4 menjadi 25-100)"
                ];
                foreach ($kets as $kline) {
                    $sheet->setCellValue("A{$row}", "• {$kline}");
                    $sheet->mergeCells("A{$row}:E{$row}");
                    $row++;
                }
                $row += 1;

                /*
                 * KATEGORI MUTU PELAYANAN
                 */
                $sheet->setCellValue("A{$row}", 'KATEGORI MUTU PELAYANAN');
                $sheet->mergeCells("A{$row}:C{$row}");
                $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle("A{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFD9E1F2');
                $row += 1;

                // Header
                $sheet->setCellValue("A{$row}", 'Kategori');
                $sheet->setCellValue("B{$row}", 'Mutu Pelayanan');
                $sheet->setCellValue("C{$row}", 'Rentang Nilai IKM');
                $sheet->getStyle("A{$row}:C{$row}")->getFont()->setBold(true);
                $sheet->getStyle("A{$row}:C{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFE7E6E6');
                $sheet->getStyle("A{$row}:C{$row}")->getBorders()->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $sheet->getStyle("A{$row}:C{$row}")->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $row++;

                $mutus = [
                    ['A', 'Sangat Baik', '88.31 - 100.00'],
                    ['B', 'Baik', '76.61 - 88.30'],
                    ['C', 'Cukup', '65.00 - 76.60'],
                    ['D', 'Kurang', '25.00 - 64.99'],
                ];
                foreach ($mutus as $m) {
                    $sheet->setCellValue("A{$row}", $m[0]);
                    $sheet->setCellValue("B{$row}", $m[1]);
                    $sheet->setCellValue("C{$row}", $m[2]);
                    $sheet->getStyle("A{$row}:C{$row}")->getBorders()->getAllBorders()
                        ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                    $sheet->getStyle("A{$row}")->getAlignment()
                        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("C{$row}")->getAlignment()
                        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $row++;
                }

                // Set column widths manually untuk menghindari tabel lain ikut memanjang
                // Kolom A: Logo & Label
                $sheet->getColumnDimension('A')->setWidth(25);

                // Kolom B-E: Untuk tabel ringkasan (Info Responden, Nilai Rata-rata, Keterangan)
                $sheet->getColumnDimension('B')->setWidth(50);  // Unsur pelayanan / deskripsi
                $sheet->getColumnDimension('C')->setWidth(15);  // NRR / nilai
                $sheet->getColumnDimension('D')->setWidth(15);  // Bobot
                $sheet->getColumnDimension('E')->setWidth(15);  // NRR Tertimbang

                // Kolom F sampai seterusnya untuk tabel utama (jika ada lebih dari 5 unsur)
                // Set width otomatis untuk kolom data responden
                for ($i = 5; $i < 26; $i++) { // F-Z
                    $col = chr(65 + $i); // Convert index to column letter
                    $sheet->getColumnDimension($col)->setWidth(12);
                }

                // Set sheet name
                $event->sheet->setTitle('Data Lengkap');
            }, // AfterSheet handler end
        ];
    }
}

// ============================================================================
// SHEET 2: Format Permenpan (Sesuai PDF)
// ============================================================================
class SurveyRespondenSheet2 implements WithEvents
{
    protected $awal;
    protected $akhir;
    protected $templateId;
    protected $user;

    public function __construct($awal = null, $akhir = null, $templateId = null, $user = null)
    {
        $this->awal = $awal;
        $this->akhir = $akhir;
        $this->templateId = $templateId;
        $this->user = $user;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Ambil data survey (sama seperti Sheet1)
                $query = Survey::with(['template', 'responses.question', 'responses.option']);

                if (!empty($this->awal) && !empty($this->akhir)) {
                    $query->whereBetween('tanggal', [$this->awal, $this->akhir]);
                }

                if (!empty($this->templateId)) {
                    $query->where('survey_template_id', $this->templateId);
                } else {
                    $query->whereNull('survey_template_id');
                }

                $surveys = $query->orderBy('tanggal')->get();

                // Get template info
                $selectedTemplate = null;
                $templateQuestions = collect();

                if ($this->templateId) {
                    $selectedTemplate = SurveyTemplate::with('questions.options')->find($this->templateId);
                    if ($selectedTemplate) {
                        $templateQuestions = $selectedTemplate->questions()
                            ->where('kode_unsur', '!=', null)
                            ->orderBy('urutan')
                            ->get();
                    }
                }

                // Build unsur mapping
                $unsurMapping = [];
                if ($templateQuestions->count() > 0) {
                    foreach ($templateQuestions as $question) {
                        $unsurMapping[$question->kode_unsur] = $question->pertanyaan ?: $question->kode_unsur;
                    }
                } else {
                    $unsurMapping = [
                        'U1' => 'Persyaratan',
                        'U2' => 'Sistem, Mekanisme, dan Prosedur',
                        'U3' => 'Waktu Penyelesaian',
                        'U4' => 'Biaya/Tarif',
                        'U5' => 'Produk Spesifikasi Jenis Pelayanan',
                        'U6' => 'Kompetensi Pelaksana',
                        'U7' => 'Perilaku Pelaksana',
                        'U8' => 'Sarana dan prasarana',
                        'U9' => 'Penanganan Pengaduan, Saran dan Masukan',
                    ];
                }

                $unsurKeys = array_keys($unsurMapping);
                $expectedUnsurCount = count($unsurKeys);

                // Build responden data (SAME LOGIC AS SHEET 1 - Multi-format support)
                $respondenData = [];
                $validIndex = 0;

                foreach ($surveys as $s) {
                    $tempResponden = [];

                    // === HYBRID: Template ID exists but data in OLD FORMAT (jawaban column) ===
                    if ($s->survey_template_id && $s->jawaban) {
                        $jawaban = $s->jawaban;

                        // Handle if jawaban is still a STRING (double-encoded JSON)
                        if (is_string($jawaban)) {
                            $jawaban = json_decode($jawaban, true);
                        }

                        if (!is_array($jawaban)) {
                            continue;
                        }

                        // Detect jawaban format and extract nilai
                        $isIndexedArray = isset($jawaban[0]);
                        $isObjectFormat = !$isIndexedArray && isset(array_values($jawaban)[0]['nilai']);

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
                            // Format: ["Sangat sesuai", "Mudah", ...]
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
                            // Format: {"U1": 4, "U2": 3, ...}
                            foreach ($unsurMapping as $kodeUnsur => $label) {
                                if (isset($jawaban[$kodeUnsur])) {
                                    $nilai = $jawaban[$kodeUnsur];
                                    if ($nilai !== null && $nilai != 0) {
                                        $tempResponden[$kodeUnsur] = floatval($nilai);
                                    }
                                }
                            }
                        }

                        // Drop if incomplete
                        if (count($tempResponden) < $expectedUnsurCount) {
                            continue;
                        }
                    }
                    // === Template-based surveys with NEW FORMAT (survey_responses table) ===
                    elseif ($s->survey_template_id && $s->responses->count() > 0) {
                        foreach ($s->responses as $response) {
                            $kodeUnsur = $response->question->kode_unsur;
                            $nilai = $response->poin;

                            if (!$kodeUnsur || $nilai === null || $nilai == 0) {
                                continue;
                            }

                            if (isset($unsurMapping[$kodeUnsur])) {
                                $tempResponden[$kodeUnsur] = floatval($nilai);
                            }
                        }

                        // Drop if incomplete
                        if (count($tempResponden) < $expectedUnsurCount) {
                            continue;
                        }
                    }
                    else {
                        // Skip: No template_id or no data
                        continue;
                    }

                    // Add demografis data to responden
                    $tempResponden['usia'] = $s->usia ?? '';
                    $tempResponden['jenis_kelamin'] = $s->jenis_kelamin ?? '';
                    $tempResponden['pekerjaan'] = $s->pekerjaan ?? '';
                    $tempResponden['pendidikan'] = $s->pendidikan ?? '';

                    // Responden valid → simpan
                    $respondenData[$validIndex] = $tempResponden;
                    $validIndex++;
                }

                $totalResponden = count($respondenData);

                // Calculate statistics
                $jumlahPerUnsur = [];
                $nrrPerUnsur = [];
                foreach ($unsurKeys as $k) {
                    $sum = 0;
                    foreach ($respondenData as $r) {
                        $sum += ($r[$k] ?? 0);
                    }
                    $jumlahPerUnsur[$k] = $sum;
                    $nrrPerUnsur[$k] = $totalResponden > 0 ? $sum / $totalResponden : 0;
                }

                // Calculate NRR Tertimbang
                $bobot = $expectedUnsurCount > 0 ? 1 / $expectedUnsurCount : 0;
                $nrrTertimbangPerUnsur = [];
                $totalNrrTertimbang = 0;
                foreach ($unsurKeys as $k) {
                    $nrrTertimbang = $nrrPerUnsur[$k] * $bobot;
                    $nrrTertimbangPerUnsur[$k] = $nrrTertimbang;
                    $totalNrrTertimbang += $nrrTertimbang;
                }

                $ikmUnit = $totalNrrTertimbang * 25;

                // Determine mutu pelayanan
                $mutu = '';
                if ($ikmUnit >= 88.31) {
                    $mutu = 'A (Sangat Baik)';
                } elseif ($ikmUnit >= 76.61) {
                    $mutu = 'B (Baik)';
                } elseif ($ikmUnit >= 65.00) {
                    $mutu = 'C (Kurang Baik)';
                } else {
                    $mutu = 'D (Tidak Baik)';
                }

                // Helper function untuk format angka
                $formatNumber = function($num, $maxDecimals = 3) {
                    $formatted = sprintf("%.{$maxDecimals}f", $num);
                    $formatted = rtrim($formatted, '0');
                    $formatted = rtrim($formatted, '.');
                    return $formatted === '' ? '0' : $formatted;
                };

                // ========================================
                // START BUILDING SHEET 2 (Format PDF)
                // ========================================

                $row = 1;

                // HEADER: Unit Pelayanan Info
                $sheet->setCellValue("A{$row}", 'PENGOLAHAN SURVEY KEPUASAN MASYARAKAT PER RESPONDEN');
                $sheet->mergeCells("A{$row}:M{$row}");
                $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $row++;

                $sheet->setCellValue("A{$row}", 'DAN PER UNSUR PELAYANAN Berdasarkan Permenpan No.14 Tahun 2017');
                $sheet->mergeCells("A{$row}:M{$row}");
                $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(10);
                $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $row++;
                $row++; // blank row

                // Unit Pelayanan Info
                $sheet->setCellValue("A{$row}", 'UNIT PELAYANAN');
                $sheet->setCellValue("B{$row}", ': Kantor Wilayah Kementerian Agama Provinsi Sulawesi Barat');
                $sheet->mergeCells("B{$row}:M{$row}");
                $row++;

                $sheet->setCellValue("A{$row}", 'ALAMAT');
                $sheet->setCellValue("B{$row}", ': Jln. H. A. M Pattana Endeng No. 46, Mamuju');
                $sheet->mergeCells("B{$row}:M{$row}");
                $row++;

                $sheet->setCellValue("A{$row}", 'Tlp/Fax.');
                $sheet->setCellValue("B{$row}", ': +62 851-8307-8072');
                $sheet->mergeCells("B{$row}:M{$row}");
                $row++;
                $row++; // blank row

                // TABLE HEADER
                $headerRow = $row;
                $sheet->setCellValue("A{$row}", 'No');
                $sheet->setCellValue("B{$row}", 'Usia');
                $sheet->setCellValue("C{$row}", 'Jenis Kelamin');
                $sheet->setCellValue("D{$row}", 'Pekerjaan');
                $sheet->setCellValue("E{$row}", 'Pendidikan');

                // Merge cell for "NILAI UNSUR PELAYANAN"
                $sheet->setCellValue("F{$row}", 'NILAI UNSUR PELAYANAN');
                $unsurEndCol = chr(69 + count($unsurKeys)); // E + count
                $sheet->mergeCells("F{$row}:{$unsurEndCol}{$row}");
                $sheet->getStyle("F{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                // Style header
                $sheet->getStyle("A{$row}:{$unsurEndCol}{$row}")->getFont()->setBold(true);
                $sheet->getStyle("A{$row}:{$unsurEndCol}{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFD3D3D3');
                $sheet->getStyle("A{$row}:{$unsurEndCol}{$row}")->getBorders()->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $row++;

                // Sub-header for unsur codes (U1, U2, etc.)
                $sheet->setCellValue("A{$row}", '');
                $sheet->setCellValue("B{$row}", '');
                $sheet->setCellValue("C{$row}", '');
                $sheet->setCellValue("D{$row}", '');
                $sheet->setCellValue("E{$row}", '');

                $colIndex = 5; // Start from F (index 5)
                foreach ($unsurKeys as $kode) {
                    $col = chr(65 + $colIndex);
                    $sheet->setCellValue("{$col}{$row}", $kode);
                    $sheet->getStyle("{$col}{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $colIndex++;
                }

                $sheet->getStyle("A{$row}:{$unsurEndCol}{$row}")->getFont()->setBold(true);
                $sheet->getStyle("A{$row}:{$unsurEndCol}{$row}")->getBorders()->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $row++;

                // DATA ROWS
                $dataStartRow = $row;
                foreach ($respondenData as $idx => $respData) {
                    $sheet->setCellValue("A{$row}", $idx + 1);
                    $sheet->setCellValue("B{$row}", $respData['usia'] ?? '');
                    $sheet->setCellValue("C{$row}", $respData['jenis_kelamin'] ?? '');
                    $sheet->setCellValue("D{$row}", $respData['pekerjaan'] ?? '');
                    $sheet->setCellValue("E{$row}", $respData['pendidikan'] ?? '');

                    // Center align demografis columns
                    $sheet->getStyle("A{$row}:E{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                    $colIndex = 5;
                    foreach ($unsurKeys as $kode) {
                        $col = chr(65 + $colIndex);
                        $sheet->setCellValue("{$col}{$row}", $respData[$kode] ?? 0);
                        $sheet->getStyle("{$col}{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $colIndex++;
                    }

                    $sheet->getStyle("A{$row}:{$unsurEndCol}{$row}")->getBorders()->getAllBorders()
                        ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                    $row++;
                }
                $dataEndRow = $row - 1;

                // FOOTER: Nilai/Unsur
                $sheet->setCellValue("A{$row}", 'Nilai');
                $sheet->setCellValue("B{$row}", '/Unsur');
                $sheet->mergeCells("A{$row}:E{$row}");

                $colIndex = 5;
                foreach ($unsurKeys as $kode) {
                    $col = chr(65 + $colIndex);
                    $sheet->setCellValue("{$col}{$row}", $jumlahPerUnsur[$kode]);
                    $sheet->getStyle("{$col}{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $colIndex++;
                }
                $sheet->getStyle("A{$row}:{$unsurEndCol}{$row}")->getFont()->setBold(true);
                $sheet->getStyle("A{$row}:{$unsurEndCol}{$row}")->getBorders()->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $row++;

                // NRR / Unsur
                $sheet->setCellValue("A{$row}", 'NRR /');
                $sheet->setCellValue("B{$row}", 'Unsur');
                $sheet->mergeCells("A{$row}:E{$row}");

                $colIndex = 5;
                foreach ($unsurKeys as $kode) {
                    $col = chr(65 + $colIndex);
                    $sheet->setCellValue("{$col}{$row}", $formatNumber($nrrPerUnsur[$kode]));
                    $sheet->getStyle("{$col}{$row}")->getNumberFormat()->setFormatCode('General');
                    $sheet->getStyle("{$col}{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $colIndex++;
                }
                $sheet->getStyle("A{$row}:{$unsurEndCol}{$row}")->getFont()->setBold(true);
                $sheet->getStyle("A{$row}:{$unsurEndCol}{$row}")->getBorders()->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $row++;

                // NRR tertbg/unsur
                $sheet->setCellValue("A{$row}", 'NRR');
                $sheet->setCellValue("B{$row}", 'tertbg/');
                $sheet->setCellValue("C{$row}", 'unsur');
                $sheet->mergeCells("A{$row}:E{$row}");

                $colIndex = 5;
                foreach ($unsurKeys as $kode) {
                    $col = chr(65 + $colIndex);
                    $sheet->setCellValue("{$col}{$row}", $formatNumber($nrrTertimbangPerUnsur[$kode]));
                    $sheet->getStyle("{$col}{$row}")->getNumberFormat()->setFormatCode('General');
                    $sheet->getStyle("{$col}{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $colIndex++;
                }

                // Add total NRR tertimbang in the last column
                $nextCol = chr(65 + $colIndex);
                $sheet->setCellValue("{$nextCol}{$row}", $formatNumber($totalNrrTertimbang));
                $sheet->getStyle("{$nextCol}{$row}")->getNumberFormat()->setFormatCode('General');

                $sheet->getStyle("A{$row}:{$nextCol}{$row}")->getFont()->setBold(true);
                $sheet->getStyle("A{$row}:{$nextCol}{$row}")->getBorders()->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $row++;

                // IKM Unit pelayanan
                $sheet->setCellValue("A{$row}", 'IKM Unit pelayanan');
                $sheet->mergeCells("A{$row}:E{$row}");
                $nextCol = chr(65 + $colIndex);
                $sheet->setCellValue("{$nextCol}{$row}", $formatNumber($ikmUnit, 2));
                $sheet->getStyle("{$nextCol}{$row}")->getNumberFormat()->setFormatCode('General');
                $sheet->getStyle("A{$row}:{$nextCol}{$row}")->getFont()->setBold(true);
                $sheet->getStyle("A{$row}:{$nextCol}{$row}")->getBorders()->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $row++;
                $row++; // blank

                // KETERANGAN PERHITUNGAN (Sesuai Permenpan RB No. 14 Tahun 2017)
                $sheet->setCellValue("A{$row}", 'KETERANGAN PERHITUNGAN (PERMENPAN RB NO. 14 TAHUN 2017)');
                $sheet->mergeCells("A{$row}:E{$row}");
                $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle("A{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFD9E1F2');
                $row++;

                $bobotValue = $formatNumber(1/$expectedUnsurCount);

                $kets = [
                    "Unsur Pelayanan yang dinilai = {$expectedUnsurCount} unsur (U1 – U{$expectedUnsurCount})",
                    "Jumlah Responden = {$totalResponden} orang",
                    "NRR per Unsur (Nilai Rata-Rata) = Σ Nilai per Unsur ÷ Jumlah Responden",
                    "Bobot per Unsur = 1 ÷ {$expectedUnsurCount} = {$bobotValue} (sama untuk semua unsur)",
                    "NRR Tertimbang = NRR per Unsur × Bobot",
                    "Total NRR Tertimbang = Σ (NRR Tertimbang semua unsur)",
                    "IKM Unit Pelayanan = Total NRR Tertimbang × 25 (konversi skala 1-4 menjadi 25-100)"
                ];

                foreach ($kets as $kline) {
                    $sheet->setCellValue("A{$row}", "• {$kline}");
                    $sheet->mergeCells("A{$row}:E{$row}");
                    $row++;
                }
                $row++;

                // IKM UNIT PELAYANAN value
                $sheet->setCellValue("A{$row}", 'IKM UNIT PELAYANAN :');
                $sheet->setCellValue("C{$row}", $formatNumber($ikmUnit, 2));
                $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(11);
                $sheet->getStyle("C{$row}")->getFont()->setBold(true)->setSize(11);
                $row++;
                $row++; // blank

                // UNSUR PELAYANAN list
                $sheet->setCellValue("B{$row}", 'No.');
                $sheet->setCellValue("C{$row}", 'UNSUR PELAYANAN');
                $sheet->getStyle("B{$row}:C{$row}")->getFont()->setBold(true);
                $row++;

                foreach ($unsurKeys as $kode) {
                    $sheet->setCellValue("B{$row}", $kode);
                    $sheet->setCellValue("C{$row}", $unsurMapping[$kode]);
                    $row++;
                }
                $row++; // blank

                // Mutu Pelayanan
                $sheet->setCellValue("A{$row}", 'Mutu Pelayanan :');
                $sheet->getStyle("A{$row}")->getFont()->setBold(true);
                $row++;

                $mutuList = [
                    ['A (Sangat Baik)', ': 88,31 - 100,00'],
                    ['B (Baik)', ': 76,61 - 88,30'],
                    ['C (Kurang Baik)', ': 65,00 - 76,60'],
                    ['D (Tidak Baik)', ': 25,00 - 64,99'],
                ];

                foreach ($mutuList as $m) {
                    $sheet->setCellValue("A{$row}", $m[0]);
                    $sheet->setCellValue("B{$row}", $m[1]);
                    $row++;
                }
                $row++;
                $row++; // blank

                // Footer signature
                $currentDate = \Carbon\Carbon::now()->translatedFormat('d F Y');
                $sheet->setCellValue("J{$row}", 'Mamuju, ' . $currentDate);
                $row++;
                $sheet->setCellValue("J{$row}", 'a.n Kepala Kantor Wilayah');
                $row++;
                $sheet->setCellValue("J{$row}", 'Kepala Bagian Tata Usaha,');
                $row++;

                // Set column widths
                $sheet->getColumnDimension('A')->setWidth(5);   // No
                $sheet->getColumnDimension('B')->setWidth(15);  // Usia
                $sheet->getColumnDimension('C')->setWidth(15);  // Jenis Kelamin
                $sheet->getColumnDimension('D')->setWidth(15);  // Pekerjaan
                $sheet->getColumnDimension('E')->setWidth(15);  // Pendidikan

                // Unsur columns
                for ($i = 5; $i < 5 + count($unsurKeys) + 1; $i++) {
                    $col = chr(65 + $i);
                    $sheet->getColumnDimension($col)->setWidth(10);
                }

                // Set sheet name
                $event->sheet->setTitle('Format Permenpan');
            },
        ];
    }
}
