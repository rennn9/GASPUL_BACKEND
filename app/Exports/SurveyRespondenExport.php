<?php

namespace App\Exports;

use App\Models\Survey;
use App\Models\SurveyTemplate;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class SurveyRespondenExport implements WithEvents
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

                // Row 2: Rata-Rata / Unsur (AVERAGE formula)
                $rataRow = $row;
                $sheet->setCellValue($colMap[$startColIndex] . $row, 'Rata-Rata / Unsur');
                for ($j = 0; $j < count($unsurKeys); $j++) {
                    $col = $colMap[$startColIndex + 1 + $j];
                    // AVERAGE formula
                    $formula = "=AVERAGE({$col}{$dataStartRow}:{$col}{$dataEndRow})";
                    $sheet->setCellValue($col . $row, $formula);
                    $sheet->getStyle($col . $row)->getNumberFormat()
                        ->setFormatCode('0.00');
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

                // Row 3: Rata-Rata Tertimbang (rata * 25)
                $tertimbangRow = $row;
                $sheet->setCellValue($colMap[$startColIndex] . $row, 'Rata-Rata Tertimbang / Unsur (×25)');
                for ($j = 0; $j < count($unsurKeys); $j++) {
                    $col = $colMap[$startColIndex + 1 + $j];
                    // Formula: rata-rata * 25
                    $formula = "={$col}{$rataRow}*25";
                    $sheet->setCellValue($col . $row, $formula);
                    $sheet->getStyle($col . $row)->getNumberFormat()
                        ->setFormatCode('0.00');
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

                // Row 4: Jumlah Rata-Rata Tertimbang (SUM of tertimbang)
                $jumlahTertimbangRow = $row;
                $sheet->setCellValue($colMap[$startColIndex] . $row, 'Jumlah Rata-Rata Tertimbang');
                $firstUnsurCol = $colMap[$startColIndex + 1];
                $lastUnsurCol = $colMap[$startColIndex + count($unsurKeys)];
                $formula = "=SUM({$firstUnsurCol}{$tertimbangRow}:{$lastUnsurCol}{$tertimbangRow})";
                $sheet->setCellValue($colMap[$startColIndex + 1] . $row, $formula);
                $sheet->getStyle($colMap[$startColIndex + 1] . $row)->getNumberFormat()
                    ->setFormatCode('0.00');
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

                // Row 5: IKM Unit Pelayanan (average of rata-rata * 25)
                $ikmRow = $row;
                $sheet->setCellValue($colMap[$startColIndex] . $row, 'IKM UNIT PELAYANAN');
                $unsurCount = count($unsurKeys);
                $formula = "=AVERAGE({$firstUnsurCol}{$rataRow}:{$lastUnsurCol}{$rataRow})*25";
                $sheet->setCellValue($colMap[$startColIndex + 1] . $row, $formula);
                $sheet->getStyle($colMap[$startColIndex + 1] . $row)->getNumberFormat()
                    ->setFormatCode('0.00');
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
                $sheet->mergeCells("A{$row}:D{$row}");
                $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle("A{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFD9E1F2');
                $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $row += 1;

                // Header
                $sheet->setCellValue("A{$row}", 'Kode');
                $sheet->setCellValue("B{$row}", 'Unsur Pelayanan');
                $sheet->setCellValue("C{$row}", 'Nilai Rata-Rata');
                $sheet->setCellValue("D{$row}", 'Nilai Tertimbang (×25)');
                $sheet->getStyle("A{$row}:D{$row}")->getFont()->setBold(true);
                $sheet->getStyle("A{$row}:D{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFE7E6E6');
                $sheet->getStyle("A{$row}:D{$row}")->getBorders()->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $sheet->getStyle("A{$row}:D{$row}")->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $row++;

                // Data rows with formulas referencing main table
                foreach ($unsurKeys as $idx => $k) {
                    $colRef = $colMap[$startColIndex + 1 + $idx]; // Column in main table

                    $sheet->setCellValue("A{$row}", $k);
                    $sheet->setCellValue("B{$row}", $unsurMapping[$k]);

                    // Reference to rata-rata cell in main table
                    $sheet->setCellValue("C{$row}", "={$colRef}{$rataRow}");
                    $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('0.00');

                    // Reference to tertimbang cell in main table
                    $sheet->setCellValue("D{$row}", "={$colRef}{$tertimbangRow}");
                    $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('0.00');

                    // Borders and alignment
                    $sheet->getStyle("A{$row}:D{$row}")->getBorders()->getAllBorders()
                        ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                    $sheet->getStyle("C{$row}:D{$row}")->getAlignment()
                        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                    $row++;
                }
                $row += 1;

                /*
                 * KETERANGAN PERHITUNGAN
                 */
                $sheet->setCellValue("A{$row}", 'KETERANGAN PERHITUNGAN');
                $sheet->mergeCells("A{$row}:D{$row}");
                $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle("A{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFD9E1F2');
                $row += 1;

                $kets = [
                    "U1 – U{$expectedUnsurCount} = Unsur-Unsur Pelayanan yang dinilai",
                    "Jumlah Responden = {$totalResponden} orang",
                    "Nilai Rata-Rata per Unsur = (Jumlah seluruh nilai unsur) ÷ (Jumlah responden)",
                    "Nilai Rata-Rata Tertimbang = Nilai Rata-Rata × 25 (konversi ke skala 0–100)",
                    "Jumlah Rata-Rata Tertimbang = SUM dari semua nilai tertimbang",
                    "IKM Unit Pelayanan = (Rata-rata dari semua unsur) × 25"
                ];
                foreach ($kets as $kline) {
                    $sheet->setCellValue("A{$row}", "• {$kline}");
                    $sheet->mergeCells("A{$row}:D{$row}");
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

                // Auto size columns
                foreach (range('A', 'Z') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // Set minimum width for readability
                $sheet->getColumnDimension('B')->setWidth(40);  // Unsur pelayanan column
            }, // AfterSheet handler end
        ];
    }
}
