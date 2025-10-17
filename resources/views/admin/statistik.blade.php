@extends('admin.layout')

@section('content')
<h2 class="fw-bold mb-4">Statistik Pelayanan</h2>

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

{{-- Chart.js CDN --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Data dari Laravel
    const statistik = @json($statistik);

    // Prepare data untuk chart
    const labels = statistik.map(item => item.bidang_layanan);
    const totalData = statistik.map(item => item.total);
    const selesaiData = statistik.map(item => item.selesai);
    const batalData = statistik.map(item => item.batal);

    // Warna untuk chart (sesuai dengan tampilan mobile)
    const colors = {
        total: 'rgba(33, 150, 243, 0.8)',      // Biru
        selesai: 'rgba(76, 175, 80, 0.8)',     // Hijau
        batal: 'rgba(244, 67, 54, 0.8)'        // Merah
    };

    // Create chart
    const ctx = document.getElementById('layananChart').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Total',
                    data: totalData,
                    backgroundColor: colors.total,
                    borderColor: 'rgba(33, 150, 243, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Selesai',
                    data: selesaiData,
                    backgroundColor: colors.selesai,
                    borderColor: 'rgba(76, 175, 80, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            indexAxis: 'y', // Horizontal bar chart seperti di mobile
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 5
                    },
                    grid: {
                        display: true,
                        drawBorder: true
                    }
                },
                y: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        boxWidth: 20,
                        padding: 15
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.x;
                        }
                    }
                }
            }
        }
    });
});
</script>

<style>
.chart-container {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
}

.card {
    border: none;
    border-radius: 10px;
}

.table th {
    background-color: #f8f9fa;
    font-weight: 600;
}

.table-bordered {
    border: 1px solid #dee2e6;
}
</style>
@endsection
