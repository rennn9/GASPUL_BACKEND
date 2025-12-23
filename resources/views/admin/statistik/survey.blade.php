    @extends('admin.layout')

    @section('content')
    @php
        // Helper function untuk format angka tanpa trailing zeros
        function formatNumber($number, $maxDecimals = 3) {
            // Gunakan sprintf untuk menghindari masalah locale
            // Format ke string dengan max decimals
            $formatted = sprintf("%.{$maxDecimals}f", $number);
            // Hilangkan trailing zeros
            $formatted = rtrim($formatted, '0');
            // Hilangkan decimal point jika tidak ada angka di belakangnya
            $formatted = rtrim($formatted, '.');
            // Jika kosong, return '0'
            return $formatted === '' ? '0' : $formatted;
        }
    @endphp

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

            {{-- DROPDOWN FILTER TEMPLATE --}}
            <div class="row mb-4">
                <div class="col-md-6">
                    <form action="{{ route('admin.statistik.survey') }}" method="GET" id="filterTemplateForm">
                        <div class="d-flex align-items-center gap-2">
                            <label for="template_id" class="form-label mb-0 fw-bold" style="white-space: nowrap;">
                                Filter Template:
                            </label>
                            <select name="template_id" id="template_id" class="form-select" onchange="this.form.submit()">
                                @foreach($allTemplates as $template)
                                    <option value="{{ $template->id }}"
                                        {{ $selectedTemplateId == $template->id ? 'selected' : '' }}>
                                        {{ $template->nama }} (v{{ $template->versi }})
                                        {{ $template->is_active ? '⭐ Aktif' : '' }}
                                    </option>
                                @endforeach
                            </select>

                            @if($selectedTemplateId)
                                <a href="{{ route('admin.statistik.survey') }}" class="btn btn-secondary btn-sm">
                                    Reset
                                </a>
                            @endif
                        </div>

                        @if(request('awal'))
                            <input type="hidden" name="awal" value="{{ request('awal') }}">
                        @endif
                        @if(request('akhir'))
                            <input type="hidden" name="akhir" value="{{ request('akhir') }}">
                        @endif
                    </form>
                </div>
                <div class="col-md-6 text-end">
                    @if($selectedTemplate)
                        <div class="alert alert-info mb-0 py-2">
                            <small>
                                <strong>Template:</strong> {{ $selectedTemplate->nama }} (Versi {{ $selectedTemplate->versi }})<br>
                                <strong>Jumlah Unsur:</strong> {{ count($unsurMapping) }}
                            </small>
                        </div>
                    @endif
                </div>
            </div>

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
        <input type="hidden" name="awal" value="{{ $periodeAwal ? $periodeAwal->format('Y-m-d') : '' }}">
        <input type="hidden" name="akhir" value="{{ $periodeAkhir ? $periodeAkhir->format('Y-m-d') : '' }}">
        @if($selectedTemplateId)
            <input type="hidden" name="template_id" value="{{ $selectedTemplateId }}">
        @endif
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

        <!-- ================== ROW 1: IKM ================== -->
        <div class="row mb-4">
            <div class="col-12">
                <h5 class="fw-bold mb-3">Nilai IKM</h5>
                <div class="p-3 border rounded bg-light text-center">
                    <h2 class="fw-bold {{ $ikmColor }} display-4">
                        {{ number_format($ikmTotal ?? 0, 2) }}
                    </h2>
                    <p class="text-muted mb-0">Indeks Kepuasan Masyarakat</p>
                </div>
            </div>
        </div>

        <!-- ================== ROW 2: Responden + Bar Chart ================== -->
        <div class="row">

            <!-- KOLOM KIRI: INFO RESPONDEN -->
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
                        <button class="btn btn-link p-0 m-0 text-decoration-none"
                                data-bs-toggle="modal"
                                data-bs-target="#modalFilterPeriode">
                            {{ $periodeAwal ? $periodeAwal->translatedFormat('d F Y') : 'N/A' }} –
                            {{ $periodeAkhir ? $periodeAkhir->translatedFormat('d F Y') : 'N/A' }}
                            <i class="bi bi-calendar-range ms-1"></i>
                        </button>

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

            <!-- KOLOM KANAN: BAR CHART RESPONDEN -->
            <div class="col-md-6">
                <h5 class="fw-bold mb-3">Visualisasi Responden</h5>

                <ul class="nav nav-tabs" id="respondenChartTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="gender-tab" data-bs-toggle="tab" data-bs-target="#chartGender" type="button" role="tab">Jenis Kelamin</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="education-tab" data-bs-toggle="tab" data-bs-target="#chartEducation" type="button" role="tab">Pendidikan</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="age-tab" data-bs-toggle="tab" data-bs-target="#chartAge" type="button" role="tab">Usia</button>
                    </li>
                </ul>

                <div class="tab-content mt-3" id="respondenChartTabsContent">
                    <div class="tab-pane fade show active" id="chartGender" role="tabpanel">
                        <canvas id="barChartGender" height="250"></canvas>
                    </div>
                    <div class="tab-pane fade" id="chartEducation" role="tabpanel">
                        <canvas id="barChartEducation" height="250"></canvas>
                    </div>
                    <div class="tab-pane fade" id="chartAge" role="tabpanel">
                        <canvas id="barChartAge" height="250"></canvas>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>
<!-- ================== END CARD UTAMA ================== -->

<!-- ================== CHART.JS ================== -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // ===== Data dari PHP/Blade =====
    const dataGender = {
        labels: ['Laki-Laki', 'Perempuan'],
        datasets: [{ label: 'Jumlah Responden', data: [{{ $laki }}, {{ $perempuan }}], backgroundColor: ['#4e73df', '#1cc88a'] }]
    };
    const dataEducation = {
        labels: [@foreach($pendidikanCounts as $pend => $count) '{{ strtoupper($pend) }}', @endforeach],
        datasets: [{ label: 'Jumlah Responden', data: [@foreach($pendidikanCounts as $pend => $count) {{ $count }}, @endforeach], backgroundColor: '#36b9cc' }]
    };
    const dataAge = {
        labels: [@foreach($usiaGroups as $g) '{{ $g['range'] }}', @endforeach],
        datasets: [{ label: 'Jumlah Responden', data: [@foreach($usiaGroups as $g) {{ $g['count'] }}, @endforeach], backgroundColor: '#f6c23e' }]
    };

    // ===== Chart config =====
    const configGender = { type: 'bar', data: dataGender, options: { responsive:true, plugins: { legend: { display: false } } } };
    const configEducation = { type: 'bar', data: dataEducation, options: { responsive:true, plugins: { legend: { display: false } } } };
    const configAge = { type: 'bar', data: dataAge, options: { responsive:true, plugins: { legend: { display: false } } } };

    // ===== Inisialisasi Chart =====
    new Chart(document.getElementById('barChartGender'), configGender);
    new Chart(document.getElementById('barChartEducation'), configEducation);
    new Chart(document.getElementById('barChartAge'), configAge);
});
</script>



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
        <td>Jumlah Nilai per Unsur</td>
        @foreach($jumlahPerUnsur as $val)
            <td>{{ formatNumber($val) }}</td>
        @endforeach
    </tr>

    <tr class="table-info fw-bold">
        <td>NRR per Unsur (Nilai Rata-Rata)</td>
        @foreach($nrrPerUnsur as $val)
            <td>{{ formatNumber($val) }}</td>
        @endforeach
    </tr>

    <tr class="table-light fw-bold">
        <td>Bobot per Unsur</td>
        @foreach($bobotPerUnsur as $val)
            <td>{{ formatNumber($val) }}</td>
        @endforeach
    </tr>

    <tr class="table-warning fw-bold">
        <td>NRR Tertimbang per Unsur</td>
        @foreach($nrrTertimbangPerUnsur as $val)
            <td>{{ formatNumber($val) }}</td>
        @endforeach
    </tr>

    <tr class="table-primary fw-bold">
        <td>Total NRR Tertimbang</td>
        <td colspan="{{ count($nrrTertimbangPerUnsur) }}">
            {{ formatNumber($totalNrrTertimbang) }}
        </td>
    </tr>

    <tr class="table-success fw-bold">
        <td>IKM Unit Pelayanan (Total NRR Tertimbang × 25)</td>
        <td colspan="{{ count($rataPerUnsur) }}">
            {{ formatNumber($ikmTotal, 2) }}
        </td>
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
                    Keterangan (Sesuai Permenpan RB No. 14 Tahun 2017)
                </div>
                <div class="card-body">
                    <ul class="mb-0 small">
                        <li class="mb-2">
                            <strong>Unsur Pelayanan:</strong> {{ count($unsurMapping) }} unsur
                            ({{ implode(', ', array_keys($unsurMapping)) }})
                        </li>

                        <li class="mb-2">
                            <strong>NRR per Unsur (Nilai Rata-Rata):</strong><br>
                            <code class="text-muted">
                                NRR = Σ Nilai per Unsur ÷ Jumlah Responden
                            </code>
                        </li>

                        <li class="mb-2">
                            <strong>Bobot per Unsur:</strong><br>
                            <code class="text-muted">
                                Bobot = 1 ÷ {{ count($unsurMapping) }} = {{ formatNumber($bobot ?? 0) }}
                            </code><br>
                            <span class="text-muted">(Bobot sama untuk semua unsur)</span>
                        </li>

                        <li class="mb-2">
                            <strong>NRR Tertimbang per Unsur:</strong><br>
                            <code class="text-muted">
                                NRR Tertimbang = NRR per Unsur × Bobot
                            </code>
                        </li>

                        <li class="mb-2">
                            <strong>Total NRR Tertimbang:</strong><br>
                            <code class="text-muted">
                                Total = Σ (NRR Tertimbang semua unsur)
                            </code>
                        </li>

                        <li>
                            <strong>IKM Unit Pelayanan:</strong><br>
                            <code class="text-muted">
                                IKM = Total NRR Tertimbang × 25
                            </code><br>
                            <span class="text-muted">(Konversi skala 1-4 menjadi 25-100)</span>
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
                    Nilai Rata-rata Per Unsur (NRR)
                </div>
                <div class="card-body">

                    <table class="table table-bordered mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 60px;">No.</th>
                                <th>Unsur Pelayanan</th>
                                <th style="width: 100px;">NRR</th>
                                <th style="width: 100px;">Bobot</th>
                                <th style="width: 100px;">NRR Tertimbang</th>
                            </tr>
                        </thead>
                        <tbody>

                            @foreach($unsurMapping as $kodeUnsur => $labelUnsur)
                            <tr>
                                <td class="text-center">{{ $kodeUnsur }}</td>
                                <td>{{ Str::limit($labelUnsur, 60) }}</td>
                                <td class="text-end">{{ formatNumber($nrrPerUnsur[$kodeUnsur] ?? 0) }}</td>
                                <td class="text-end">{{ formatNumber($bobotPerUnsur[$kodeUnsur] ?? 0) }}</td>
                                <td class="text-end">{{ formatNumber($nrrTertimbangPerUnsur[$kodeUnsur] ?? 0) }}</td>
                            </tr>
                            @endforeach

                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="2" class="text-end">Total NRR Tertimbang:</td>
                                <td colspan="3" class="text-end">{{ formatNumber($totalNrrTertimbang ?? 0) }}</td>
                            </tr>
                            <tr class="table-success">
                                <td colspan="2" class="text-end">IKM (× 25):</td>
                                <td colspan="3" class="text-end">{{ formatNumber($ikmTotal ?? 0, 2) }}</td>
                            </tr>
                            <tr class="table-info">
                                <td colspan="2" class="text-end">Kategori:</td>
                                <td colspan="3" class="text-end">{{ $mutuTotal ?? '-' }}</td>
                            </tr>
                        </tfoot>
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
                                value="{{ request('awal') ?? ($periodeAwal ? $periodeAwal->format('Y-m-d') : '') }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tanggal Akhir</label>
                            <input type="date"
                                name="akhir"
                                class="form-control"
                                value="{{ request('akhir') ?? ($periodeAkhir ? $periodeAkhir->format('Y-m-d') : '') }}">
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