@extends('admin.layout')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold">Layanan Konsultasi</h2>
</div>

{{-- Filter Status --}}
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.konsultasi') }}" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Filter Status</label>
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="semua" {{ request('status') == 'semua' ? 'selected' : '' }}>Semua Status</option>
                    <option value="baru" {{ request('status') == 'baru' ? 'selected' : '' }}>Baru</option>
                    <option value="proses" {{ request('status') == 'proses' ? 'selected' : '' }}>Proses</option>
                    <option value="selesai" {{ request('status') == 'selesai' ? 'selected' : '' }}>Selesai</option>
                    <option value="batal" {{ request('status') == 'batal' ? 'selected' : '' }}>Batal</option>
                </select>
            </div>
        </form>
    </div>
</div>

{{-- Tabel Konsultasi --}}
<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Nama Pemohon</th>
                        <th>No HP/WA</th>
                        <th>Email</th>
                        <th>Perihal</th>
                        <th>Isi Konsultasi</th>
                        <th>Dok Upload</th>
                        <th>Status</th>
                        <th>Tanggal Konsultasi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($konsultasis as $index => $item)
                    <tr>
                        <td>{{ $konsultasis->firstItem() + $index }}</td>
                        <td>{{ $item->nama_lengkap }}</td>
                        <td>{{ $item->no_hp }}</td>
                        <td>{{ $item->email ?? '-' }}</td>
                        <td>{{ $item->perihal }}</td>
                        <td>
                            <div style="max-width: 300px; white-space: pre-wrap;">
                                {{ Str::limit($item->isi_konsultasi, 100) }}
                            </div>
                        </td>
                        <td class="text-center">
                            @if($item->dokumen)
                                <a href="{{ asset('storage/' . $item->dokumen) }}" target="_blank" class="btn btn-sm btn-info">
                                    <i class="bi bi-download"></i> Lihat
                                </a>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <select class="form-select form-select-sm status-dropdown" data-id="{{ $item->id }}">
                                <option value="baru" {{ $item->status === 'baru' ? 'selected' : '' }}>Baru</option>
                                <option value="proses" {{ $item->status === 'proses' ? 'selected' : '' }}>Proses</option>
                                <option value="selesai" {{ $item->status === 'selesai' ? 'selected' : '' }}>Selesai</option>
                                <option value="batal" {{ $item->status === 'batal' ? 'selected' : '' }}>Batal</option>
                            </select>
                        </td>
                        <td>{{ \Carbon\Carbon::parse($item->tanggal_konsultasi)->translatedFormat('d/m/Y H:i') }}</td>
                        <td>
                            <form action="{{ route('admin.konsultasi.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus data ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">Belum ada data konsultasi.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div class="text-muted">
                Menampilkan {{ $konsultasis->firstItem() ?? 0 }} sampai {{ $konsultasis->lastItem() ?? 0 }} dari {{ $konsultasis->total() }} data
            </div>
            <div>
                {{ $konsultasis->appends(request()->query())->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function(){

    // Fungsi untuk memberi warna pada dropdown status
    function applyStatusColor(selectEl){
        selectEl.classList.remove('bg-info','bg-warning','bg-success','bg-danger','text-white');
        switch(selectEl.value){
            case 'baru':
                selectEl.classList.add('bg-info','text-white');
                break;
            case 'proses':
                selectEl.classList.add('bg-warning','text-dark');
                break;
            case 'selesai':
                selectEl.classList.add('bg-success','text-white');
                break;
            case 'batal':
                selectEl.classList.add('bg-danger','text-white');
                break;
        }
    }

    // Event handler untuk dropdown status
    document.querySelectorAll('.status-dropdown').forEach(dropdown => {
        applyStatusColor(dropdown);

        dropdown.onchange = function(){
            fetch("{{ url('admin/konsultasi/status') }}/" + this.dataset.id, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({ status: this.value })
            })
            .then(r => r.json())
            .then(data => {
                if(data.success) {
                    applyStatusColor(this);
                    // Show toast or alert
                    alert('Status berhasil diupdate');
                } else {
                    alert('Gagal update status');
                }
            })
            .catch(e => {
                console.error(e);
                alert('Error update status');
            });
        }
    });
});
</script>

<style>
.table th {
    background-color: #f8f9fa;
    font-weight: 600;
    white-space: nowrap;
}

.status-dropdown {
    min-width: 100px;
}

.card {
    border: none;
    border-radius: 10px;
}
</style>
@endsection
