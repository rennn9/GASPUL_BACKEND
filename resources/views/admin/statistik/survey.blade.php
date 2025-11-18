    @extends('admin.layout')

    @section('content')
    <h2 class="fw-bold mb-4">Statistik Survey (IKM)</h2>

    {{-- Navigasi Tab --}}
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link" href="{{ route('admin.statistik.pelayanan') }}">Statistik Pelayanan</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('admin.statistik.konsultasi') }}">Statistik Konsultasi</a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" href="{{ route('admin.statistik.survey') }}">Statistik Survey (IKM)</a>
        </li>
    </ul>

    <div class="card shadow-sm mb-4">
        <div class="card-body">

            @php
                $totalResponden = $totalResponden ?? 0;
                $rataPerUnsur = $rataPerUnsur ?? [];
                $respondenData = $respondenData ?? [];

                // DATA SURVEYS (dipakai untuk card utama)
                $surveys = $surveys ?? collect();

                // --- RESPONDEN ---
                $total = $surveys->count();
                $laki = $surveys->where('jenis_kelamin', 'Laki-laki')->count();
                $perempuan = $surveys->where('jenis_kelamin', 'Perempuan')->count();

                // --- PENDIDIKAN ---
                $pendidikanCounts = $surveys->groupBy('pendidikan')->map->count();

                // --- INTERVAL USIA DINAMIS ---
                $minUsia = $surveys->min('usia');
                $maxUsia = $surveys->max('usia');
                $interval = 10;
                $usiaGroups = [];

                if ($minUsia && $maxUsia) {
                    for ($start = $minUsia; $start <= $maxUsia; $start += $interval) {
                        $end = $start + $interval - 1;

                        $count = $surveys->filter(function ($item) use ($start, $end) {
                            return $item->usia >= $start && $item->usia <= $end;
                        })->count();

                        $usiaGroups[] = [
                            'range' => "$start - $end",
                            'count' => $count
                        ];
                    }
                }

                // --- PERIODE SURVEY ---
                $periodeAwal = $surveys->min('tanggal');
                $periodeAkhir = $surveys->max('tanggal');
            @endphp

            @if($totalResponden > 0)

            @php
                $ikmColor = 'text-secondary'; // default

                if ($ikmTotal >= 88.31) {
                    $ikmColor = 'text-success';      // Hijau — Sangat Baik
                } elseif ($ikmTotal >= 76.61) {
                    $ikmColor = 'text-primary';      // Biru — Baik
                } elseif ($ikmTotal >= 65.00) {
                    $ikmColor = 'text-warning';      // Kuning — Kurang Baik
                } else {
                    $ikmColor = 'text-danger';       // Merah — Tidak Baik
                }
            @endphp


<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Tabel Perhitungan IKM per Responden</h4>

    <!-- Tombol Download Excel -->
    <form action="{{ route('admin.statistik.survey.downloadExcel') }}" method="GET">
        <input type="hidden" name="awal" value="{{ $periodeAwal }}">
        <input type="hidden" name="akhir" value="{{ $periodeAkhir }}">
        <button type="submit" class="btn btn-success">
            <i class="bi bi-file-earmark-excel"></i> Download Excel
        </button>
    </form>
</div>


            <!-- ================== CARD UTAMA ================== -->
            <div class="card shadow mb-4">
                <div class="card-header bg-dark text-white fw-bold">
                    Ringkasan Survey
                </div>

                <div class="card-body">
                    <div class="row">

                        <!-- KOLOM KIRI -->
                        <div class="col-md-6">
                            <h5 class="fw-bold mb-3">Nilai IKM</h5>

                            <div class="p-3 border rounded bg-light">
                                <h2 class="text-center fw-bold {{ $ikmColor }} display-4">
                                    {{ number_format($ikmTotal ?? 0, 2) }}
                                </h2>
                                <p class="text-center text-muted mb-0">Indeks Kepuasan Masyarakat</p>
                            </div>
                        </div>

                        <!-- KOLOM KANAN -->
                        <div class="col-md-6">
                            <h5 class="fw-bold mb-3">Responden</h5>

                            <div class="p-3 border rounded bg-light">

                                <p class="mb-2">
                                    <strong>Total Responden:</strong> {{ $total }}
                                </p>

                                <p class="mb-2">
                                    <strong>Jenis Kelamin:</strong><br>
                                    - Laki-Laki: {{ $laki }} <br>
                                    - Perempuan: {{ $perempuan }}
                                </p>

                                <p class="mb-2">
                                    <strong>Pendidikan:</strong><br>
                                    @foreach($pendidikanCounts as $pend => $count)
                                        - {{ strtoupper($pend) }}: {{ $count }} <br>
                                    @endforeach
                                </p>

                                <p class="mb-2">
                                    <strong>Usia (Interval):</strong><br>
                                    @foreach($usiaGroups as $g)
                                        - {{ $g['range'] }} tahun : {{ $g['count'] }} <br>
                                    @endforeach
                                </p>

                            <p class="mb-0">
                                <strong>Periode Survey:</strong><br>

                                <!-- Tombol buka modal datepicker -->
                                <button class="btn btn-link p-0 m-0 text-decoration-none"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalFilterPeriode">
                                    {{ $periodeAwal?->translatedFormat('d F Y') }} –
                                    {{ $periodeAkhir?->translatedFormat('d F Y') }}
                                    <i class="bi bi-calendar-range ms-1"></i>
                                </button>

                                <!-- Tombol reset periode (muncul hanya jika filter aktif) -->
                                @if($periodeAwal || $periodeAkhir)
                            <form action="{{ route('admin.statistik.survey.resetPeriode') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-secondary">
                                    Reset Periode
                                </button>
                            </form>


                                @endif
                            </p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <!-- ================== END CARD UTAMA ================== -->



<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Tabel Perhitungan IKM</h5>
    </div>

    <div class="card-body">

        <div style="overflow-x:auto; border: 1px solid #dee2e6; border-radius: .25rem;">

            {{-- Header --}}
            <table class="table table-bordered table-striped text-center mb-0" style="table-layout: fixed; width: 100%;">
                <thead class="table-light" style="position: sticky; top: 0; background: #f8f9fa; z-index: 10;">
                    <tr>
                        <th>Responden</th>
                        @foreach(array_keys($rataPerUnsur) as $pertanyaan)
                            <th>{{ Str::limit($pertanyaan, 40) }}</th>
                        @endforeach
                    </tr>
                </thead>
            </table>

            {{-- Scrollable body --}}
            <div style="
                max-height: 400px; 
                overflow-y: overlay;
                -webkit-overflow-scrolling: touch;
            ">
                <table class="table table-bordered table-striped text-center mb-0" style="table-layout: fixed; width: 100%;">
                    <tbody>
                        @foreach($respondenData as $index => $responden)
                            <tr>
                                <td>Responden {{ $index + 1 }}</td>
                                @foreach($rataPerUnsur as $pertanyaan => $_)
                                    <td>{{ $responden[$pertanyaan] ?? '-' }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Footer --}}
            <table class="table table-bordered table-striped text-center mb-0" style="table-layout: fixed; width: 100%;">
                <tfoot>

                    <tr class="table-secondary fw-bold">
                        <td>Jumlah Nilai / Unsur</td>
                        @foreach($rataPerUnsur as $pertanyaan => $_)
                            @php
                                $sum = 0;
                                foreach($respondenData as $responden){
                                    $sum += $responden[$pertanyaan] ?? 0;
                                }
                            @endphp
                            <td>{{ $sum }}</td>
                        @endforeach
                    </tr>

                    <tr class="table-info fw-bold">
                        <td>Rata-Rata / Unsur</td>
                        @foreach($rataPerUnsur as $pertanyaan => $_)
                            @php
                                $sum = 0;
                                foreach($respondenData as $responden){
                                    $sum += $responden[$pertanyaan] ?? 0;
                                }
                                $avg = $totalResponden > 0 ? $sum / $totalResponden : 0;
                            @endphp
                            <td>{{ number_format($avg, 2) }}</td>
                        @endforeach
                    </tr>

                    <tr class="table-warning fw-bold">
                        <td>Rata-Rata Tertimbang / Unsur</td>
                        @foreach($rataPerUnsur as $pertanyaan => $_)
                            @php
                                $sum = 0;
                                foreach($respondenData as $responden){
                                    $sum += $responden[$pertanyaan] ?? 0;
                                }
                                $avg = $totalResponden > 0 ? $sum / $totalResponden : 0;
                                $weighted = $avg * 25;
                            @endphp
                            <td>{{ number_format($weighted, 2) }}</td>
                        @endforeach
                    </tr>

                    <tr class="table-primary fw-bold">
                        <td>Jumlah Rata-Rata Tertimbang</td>
                        @php
                            $totalWeighted = 0;
                            foreach($rataPerUnsur as $pertanyaan => $_){
                                $sum = 0;
                                foreach($respondenData as $responden){
                                    $sum += $responden[$pertanyaan] ?? 0;
                                }
                                $avg = $totalResponden > 0 ? $sum / $totalResponden : 0;
                                $weighted = $avg * 25;
                                $totalWeighted += $weighted;
                            }
                        @endphp
                        <td colspan="{{ count($rataPerUnsur) }}">{{ number_format($totalWeighted, 2) }}</td>
                    </tr>

                    <tr class="table-success fw-bold">
                        <td>IKM Unit Pelayanan</td>
                        @php
                            $sumAvg = 0;
                            foreach($rataPerUnsur as $pertanyaan => $_){
                                $sum = 0;
                                foreach($respondenData as $responden){
                                    $sum += $responden[$pertanyaan] ?? 0;
                                }
                                $avg = $totalResponden > 0 ? $sum / $totalResponden : 0;
                                $sumAvg += $avg;
                            }
                            $ikmUnit = count($rataPerUnsur) > 0 ? ($sumAvg / count($rataPerUnsur)) * 25 : 0;
                        @endphp
                        <td colspan="{{ count($rataPerUnsur) }}">{{ number_format($ikmUnit, 2) }}</td>
                    </tr>

                </tfoot>
            </table>
        </div>

    </div>
</div>

{{-- CSS untuk firefox --}}
<style>
    @supports (scrollbar-width: thin) {
        div[style*="overflow-y: overlay"] {
            scrollbar-width: thin;
            scrollbar-color: rgba(0,0,0,0.3) rgba(0,0,0,0.05);
        }
    }
</style>



    <div class="row mt-4">

        <!-- ================== CARD KIRI ================== -->
        <div class="col-md-6 mb-3">

            <!-- KETERANGAN -->
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-secondary text-white fw-bold">
                    Keterangan
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li class="mb-2">
                            <strong>U1 – U9</strong> = Unsur-Unsur Pelayanan
                        </li>

                        <li class="mb-2">
                            <strong>Nilai Rata-Rata per Unsur</strong><br>
                            <span class="text-muted">
                                (Jumlah seluruh nilai unsur) ÷ (Jumlah responden)
                            </span>
                        </li>

                        <li class="mb-2">
                            <strong>Nilai Rata-Rata Tertimbang</strong><br>
                            <span class="text-muted">
                                Nilai Rata-Rata × 25 (Konversi sesuai pedoman IKM)
                            </span>
                        </li>

                        <li>
                            <strong>IKM Unit Pelayanan</strong><br>
                            <span class="text-muted">
                                Jumlah Rata-Rata Tertimbang dari 9 unsur ÷ 9
                            </span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- MUTU PELAYANAN -->
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white fw-bold">
                    Mutu Pelayanan
                </div>
                <div class="card-body">
                    <table class="table table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Mutu</th>
                                <th>Rentang Nilai</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>A (Sangat Baik)</td><td>88,31 - 100,00</td></tr>
                            <tr><td>B (Baik)</td><td>76,61 - 88,30</td></tr>
                            <tr><td>C (Kurang Baik)</td><td>65,00 - 76,60</td></tr>
                            <tr><td>D (Tidak Baik)</td><td>25,00 - 64,99</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- ================== CARD KANAN ================== -->
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white fw-bold">
                    Nilai Rata-rata Per Unsur
                </div>
                <div class="card-body">

                    <table class="table table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No.</th>
                                <th>Unsur Pelayanan</th>
                                <th>Nilai Rata-rata</th>
                            </tr>
                        </thead>
                        <tbody>

                            @php
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
                            @endphp

                            @foreach($unsurLabels as $kode => $label)
                            <tr>
                                <td>{{ $kode }}</td>
                                <td>{{ $label }}</td>
                                <td>{{ $rataPerUnsur[$kode] ?? '-' }}</td>
                            </tr>
                            @endforeach

                        </tbody>
                    </table>

                </div>
            </div>
        </div>

    </div>


            @else
                <div class="text-center text-muted py-4">
                    Belum ada data survei untuk ditampilkan.
                </div>
            @endif

        </div>
    </div>

    <!-- Modal Filter Periode -->
    <div class="modal fade" id="modalFilterPeriode" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">

            <form action="{{ route('admin.statistik.survey') }}" method="GET">
                    <div class="modal-header">
                        <h5 class="modal-title">Filter Periode Survey</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">

                        <div class="mb-3">
                            <label class="form-label">Tanggal Awal</label>
                            <input type="date"
                                name="awal"
                                class="form-control"
                                value="{{ request('awal') ?? $periodeAwal->format('Y-m-d') }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tanggal Akhir</label>
                            <input type="date"
                                name="akhir"
                                class="form-control"
                                value="{{ request('akhir') ?? $periodeAkhir->format('Y-m-d') }}">
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Terapkan</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Batal
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>

    @endsection
