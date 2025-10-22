@extends('admin.layout')

@section('content')
<h2 class="fw-bold mb-4">Statistik Pelayanan</h2>

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
        {{-- Grafik Layanan --}}
        <h4 class="text-center mb-4">Grafik Layanan</h4>
        <div class="chart-container mb-5" style="position: relative; height: 400px;">
            <canvas id="layananChart"></canvas>
        </div>

        {{-- Tabel Layanan --}}
        <h4 class="text-center mb-4">Tabel Layanan</h4>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-light">
                    <tr>
                        <th>Bidang Pelayanan</th>
                        <th class="text-center">Total</th>
                        <th class="text-center">Selesai</th>
                        <th class="text-center">Batal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($statistik as $item)
                    <tr>
                        <td>{{ $item->bidang_layanan }}</td>
                        <td class="text-center">{{ $item->total }}</td>
                        <td class="text-center">{{ $item->selesai }}</td>
                        <td class="text-center">{{ $item->batal }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">Belum ada data statistik.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const statistik = @json($statistik);
    const labels = statistik.map(item => item.bidang_layanan);
    const totalData = statistik.map(item => item.total);
    const selesaiData = statistik.map(item => item.selesai);
    const batalData = statistik.map(item => item.batal);

    const colors = {
        total: 'rgba(33, 150, 243, 0.8)',
        selesai: 'rgba(76, 175, 80, 0.8)',
        batal: 'rgba(244, 67, 54, 0.8)'
    };

    const ctx = document.getElementById('layananChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                { label: 'Total', data: totalData, backgroundColor: colors.total },
                { label: 'Selesai', data: selesaiData, backgroundColor: colors.selesai },
                { label: 'Batal', data: batalData, backgroundColor: colors.batal },
            ]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { beginAtZero: true, ticks: { stepSize: 5 } },
                y: { grid: { display: false } }
            },
            plugins: { legend: { display: true, position: 'bottom' } }
        }
    });
});
</script>

<style>
.chart-container { background: #fff; padding: 20px; border-radius: 8px; }
.card { border: none; border-radius: 10px; }
.table th { background-color: #f8f9fa; font-weight: 600; }
.table-bordered { border: 1px solid #dee2e6; }
</style>
@endsection
