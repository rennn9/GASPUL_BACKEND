@extends('admin.layout')

@section('content')
<h2 class="fw-bold mb-4">Statistik Konsultasi</h2>

{{-- Navigasi Tab --}}
<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link {{ request()->is('admin/statistik/pelayanan') ? 'active' : '' }}" href="{{ route('admin.statistik.pelayanan') }}">
            Statistik Pelayanan
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->is('admin/statistik/konsultasi') ? 'active' : '' }}" href="{{ route('admin.statistik.konsultasi') }}">
            Statistik Konsultasi
        </a>
    </li>
</ul>

<div class="card shadow-sm">
    <div class="card-body">
        {{-- Grafik Konsultasi --}}
        <h4 class="text-center mb-4">Grafik Konsultasi</h4>
        <div class="chart-container mb-5" style="position: relative; height: 400px;">
            <canvas id="konsultasiChart"></canvas>
        </div>

        {{-- Tabel Konsultasi --}}
        <h4 class="text-center mb-4">Tabel Konsultasi</h4>
        <div class="table-responsive">
            <table class="table table-bordered table-striped text-center">
                <thead class="table-light">
                    <tr>
                        <th>Total</th>
                        <th>Selesai</th>
                        <th>Batal</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $statistik->total ?? 0 }}</td>
                        <td>{{ $statistik->selesai ?? 0 }}</td>
                        <td>{{ $statistik->batal ?? 0 }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const statistik = @json($statistik);

    const labels = ['Total', 'Selesai', 'Batal'];
    const data = [
        statistik.total ?? 0,
        statistik.selesai ?? 0,
        statistik.batal ?? 0
    ];

    const colors = ['rgba(33, 150, 243, 0.8)', 'rgba(76, 175, 80, 0.8)', 'rgba(244, 67, 54, 0.8)'];

    const ctx = document.getElementById('konsultasiChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Jumlah',
                data: data,
                backgroundColor: colors
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { beginAtZero: true, ticks: { stepSize: 5 } },
                y: { grid: { display: false } }
            },
            plugins: { legend: { display: false } }
        }
    });
});
</script>
@endsection
