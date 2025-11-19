@extends('admin.layout')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold">Layanan Konsultasi</h2>
</div>

{{-- Filter Status + Tombol Download --}}
<div class="card shadow-sm mb-3">
    <div class="card-body d-flex align-items-end gap-3">
        <form method="GET" action="{{ route('admin.konsultasi') }}" class="d-flex gap-2 align-items-end">
            <div class="mb-0">
                <label class="form-label">Filter Status</label>
                <select name="status" id="statusFilter" class="form-select">
                    <option value="semua" {{ request('status') == 'semua' ? 'selected' : '' }}>Semua Status</option>
                    <option value="baru" {{ request('status') == 'baru' ? 'selected' : '' }}>Baru</option>
                    <option value="proses" {{ request('status') == 'proses' ? 'selected' : '' }}>Proses</option>
                    <option value="selesai" {{ request('status') == 'selesai' ? 'selected' : '' }}>Selesai</option>
                    <option value="batal" {{ request('status') == 'batal' ? 'selected' : '' }}>Batal</option>
                </select>
            </div>
        </form>

        {{-- Tombol Download PDF --}}
        <button id="download-pdf-btn" class="btn btn-success mb-0">
            <i class="bi bi-file-earmark-pdf"></i> <span id="download-pdf-text">Download PDF</span>
        </button>
    </div>
</div>

{{-- Tabel Konsultasi --}}
<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive" id="konsultasi-table-container">
            <table class="table table-bordered table-striped table-hover align-middle" id="konsultasi-table">
                <thead class="table-light text-center">
                    <tr>
                        <th>No.</th>
                        <th>Nomor Antrian</th>
                        <th>Nama Lengkap</th>
                        <th>Email</th>
                        <th>No. HP / WA</th>
                        <th>Alamat</th>
                        <th>Asal Instansi</th>
                        <th>Perihal</th>
                        <th>Dok Upload</th>
                        <th>Tanggal Layanan</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($konsultasis as $index => $item)
                    <tr id="konsultasi-row-{{ $item->id }}">
                        <td class="text-center">{{ $konsultasis->firstItem() + $index }}</td>
                        <td class="fw-bold text-center">{{ $item->nomor_antrian ?? ($item->antrian->nomor_antrian ?? '-') }}</td>
                        <td>{{ $item->nama_lengkap }}</td>
                        <td>{{ $item->email ?? '-' }}</td>
                        <td>{{ $item->no_hp_wa }}</td>
                        <td>{{ $item->alamat ?? '-' }}</td>
                        <td>{{ $item->asal_instansi ?? '-' }}</td>
                        <td>{{ $item->perihal }}</td>

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
                            {{ \Carbon\Carbon::parse($item->tanggal_layanan)->translatedFormat('l, d/m/Y') }}
                        </td>

                        <td>
                            <select class="form-select form-select-sm status-dropdown" data-id="{{ $item->id }}">
                                <option value="baru" {{ $item->status === 'baru' ? 'selected' : '' }}>Baru</option>
                                <option value="proses" {{ $item->status === 'proses' ? 'selected' : '' }}>Proses</option>
                                <option value="selesai" {{ $item->status === 'selesai' ? 'selected' : '' }}>Selesai</option>
                                <option value="batal" {{ $item->status === 'batal' ? 'selected' : '' }}>Batal</option>
                            </select>
                        </td>

                        <td class="text-center">
                            <button class="btn btn-sm btn-danger delete-btn" data-id="{{ $item->id }}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="12" class="text-center text-muted py-4">Belum ada data konsultasi.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div class="text-muted small">
                Menampilkan {{ $konsultasis->firstItem() ?? 0 }} sampai {{ $konsultasis->lastItem() ?? 0 }} dari {{ $konsultasis->total() }} data
            </div>
            <div>
                {{ $konsultasis->appends(request()->query())->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>

{{-- Script --}}
<script>
document.addEventListener("DOMContentLoaded", function(){
    const statusSelect = document.getElementById('statusFilter');
    const downloadBtn = document.getElementById('download-pdf-btn');
    const downloadText = document.getElementById('download-pdf-text');

    function updateDownloadTitle() {
        const selected = statusSelect.options[statusSelect.selectedIndex].text;
        downloadText.textContent = `Download daftar konsultasi - ${selected.toLowerCase()}`;
        downloadBtn.title = `Download daftar konsultasi - ${selected.toLowerCase()}`;
    }
    updateDownloadTitle();
    statusSelect.addEventListener('change', updateDownloadTitle);

    downloadBtn.onclick = function(){
        const status = statusSelect.value;
        fetch("{{ route('admin.konsultasi.pdf') }}?status=" + status)
            .then(res => res.json())
            .then(data => {
                if(data.success) window.open(data.url, '_blank');
                else alert('Gagal generate PDF');
            })
            .catch(() => alert('Terjadi error saat generate PDF'));
    };

    function applyStatusColor(selectEl){
        selectEl.classList.remove('bg-info','bg-warning','bg-success','bg-danger','text-white','text-dark');
        switch(selectEl.value){
            case 'baru': selectEl.classList.add('bg-info','text-white'); break;
            case 'proses': selectEl.classList.add('bg-warning','text-dark'); break;
            case 'selesai': selectEl.classList.add('bg-success','text-white'); break;
            case 'batal': selectEl.classList.add('bg-danger','text-white'); break;
        }
    }

    function attachStatusEvents() {
        document.querySelectorAll('.status-dropdown').forEach(dropdown => {
            applyStatusColor(dropdown);
            dropdown.onchange = function(){
                const id = this.dataset.id;
                const status = this.value;
                fetch("{{ url('admin/konsultasi/status') }}/" + id, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({ status })
                })
                .then(r => r.json())
                .then(data => {
                    if(data.success) applyStatusColor(this);
                    else alert('Gagal update status');
                })
                .catch(() => alert('Terjadi error saat update status'));
            };
        });
    }

    function attachDeleteEvents() {
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.onclick = function(){
                if(!confirm("Yakin ingin menghapus data konsultasi ini?")) return;
                const id = this.dataset.id;
                fetch("{{ url('admin/konsultasi') }}/" + id, {
                    method: "DELETE",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    }
                })
                .then(r => r.json())
                .then(data => {
                    if(data.success){
                        const row = document.getElementById(`konsultasi-row-${id}`);
                        if(row) row.remove();
                    } else alert('Gagal menghapus data');
                })
                .catch(() => alert('Terjadi error saat menghapus data'));
            };
        });
    }

    attachStatusEvents();
    attachDeleteEvents();
});
</script>

<style>
.table th { background-color: #f8f9fa; font-weight: 600; white-space: nowrap; }
.status-dropdown { min-width: 110px; }
.card { border: none; border-radius: 10px; }
#download-pdf-btn { margin-bottom: 0; }
</style>
@endsection
