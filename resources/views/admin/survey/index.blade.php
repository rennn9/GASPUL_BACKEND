@extends('admin.layout')

@section('content')
<div class="container mt-4">

    {{-- =======================
         Title
         ======================= --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="fw-bold">Data Survey Pengguna</h2>
    </div>

    {{-- =======================
         Filter Waktu + Download
         ======================= --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body d-flex flex-wrap align-items-start gap-2">
            {{-- Tabs filter --}}
            <ul class="nav nav-tabs" id="filterTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ ($filter ?? 'today') === 'today' ? 'active' : '' }}" 
                       href="{{ route('admin.survey.index', ['filter' => 'today']) }}">
                       Hari ini
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ ($filter ?? '') === 'all' ? 'active' : '' }}" 
                       href="{{ route('admin.survey.index', ['filter' => 'all']) }}">
                       Semua
                    </a>
                </li>
            </ul>

            {{-- Date picker --}}
            <form method="GET" action="{{ route('admin.survey.index') }}" class="d-flex gap-2 align-items-center ms-2">
                <input type="hidden" name="filter" value="custom">
                <input type="date" name="date" value="{{ $date ?? '' }}" class="form-control" onchange="this.form.submit()">
            </form>

            {{-- Download PDF --}}
            @php
                $downloadTitle = match($filter ?? 'all') {
                    'today' => 'Download PDF Survey - Hari ini',
                    'all'   => 'Download PDF Survey - Semua',
                    'custom'=> 'Download PDF Survey - ' . ($date ?? '-'),
                    default => 'Download PDF Survey'
                };
            @endphp
            <a href="{{ route('admin.survey.download', ['filter' => $filter ?? 'all', 'date' => $date ?? '']) }}" 
               class="btn btn-success ms-2" target="_blank">
                <i class="bi bi-download"></i> {{ $downloadTitle }}
            </a>
        </div>
    </div>


    {{-- =======================
         Tabel Survey
         ======================= --}}
    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-striped align-middle">
                <thead class="table-primary">
                    <tr>
                        <th>#</th>
                        <th>Nama</th>
                        <th>Bidang</th>
                        <th>Pekerjaan</th>
                        <th>Tanggal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($surveys as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $item->nama_responden }}</td>
                            <td>{{ $item->bidang }}</td>
                            <td>{{ $item->pekerjaan }}</td>
                            <td>
                                {{ optional($item->tanggal)
                                    ? \Carbon\Carbon::parse($item->tanggal)
                                        ->locale('id')
                                        ->translatedFormat('l, d/m/Y')
                                    : '-' }}
                            </td>
                            <td>
                                <a href="{{ route('admin.survey.show', $item->id) }}" class="btn btn-sm btn-info">
                                    <i class="bi bi-eye"></i> Detail
                                </a>
                                <button class="btn btn-sm btn-danger" onclick="deleteSurvey({{ $item->id }})">
                                    <i class="bi bi-trash"></i> Hapus
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">Belum ada data survey</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="mt-3">
                {{ $surveys->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>

<script>
function deleteSurvey(id) {
    if (!confirm('Apakah Anda yakin ingin menghapus survey ini?')) return;

    fetch("{{ route('admin.survey.destroy', ['id' => 'REPLACE_ID']) }}".replace('REPLACE_ID', id), {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': "{{ csrf_token() }}",
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            alert('Survey berhasil dihapus');
            location.reload();
        } else {
            alert(res.message || 'Gagal menghapus survey');
        }
    })
    .catch(err => alert('Terjadi kesalahan: ' + err));
}
</script>
@endsection
