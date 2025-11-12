<table class="table table-bordered table-striped table-hover align-middle">
    <thead class="table-light">
        <tr class="text-center">
            <th>No.</th>
            <th>Nomor Antrian</th>
            <th>Nama Lengkap</th>
            <th>Email</th>
            <th>No. HP / WA</th>
            <th>Alamat</th>
            <th>Bidang Layanan</th>
            <th>Layanan</th>
            <th>Tanggal Layanan</th>
            <th>Keterangan</th> {{-- ✅ kolom baru --}}
            <th>Status</th>
            <th>Aksi</th>
        </tr>
    </thead>

    <tbody>
        @forelse($antrian as $index => $item)
            <tr id="antrian-row-{{ $item->id }}">
                <td class="text-center">{{ $antrian->firstItem() + $index }}</td>
                <td class="fw-bold text-center">{{ $item->nomor_antrian }}</td>
                <td>{{ $item->nama_lengkap ?? '-' }}</td>
                <td>{{ $item->email ?? '-' }}</td>
                <td>{{ $item->no_hp_wa ?? '-' }}</td>
                <td>{{ $item->alamat ?? '-' }}</td>
                <td>{{ $item->bidang_layanan ?? '-' }}</td>
                <td>{{ $item->layanan ?? '-' }}</td>
                <td>{{ \Carbon\Carbon::parse($item->tanggal_layanan)->translatedFormat('l, d/m/Y') }}</td>
                <td>{{ $item->keterangan ?? '-' }}</td> {{-- ✅ tampilkan keterangan --}}
                <td>
                    <select class="form-select form-select-sm status-dropdown" data-id="{{ $item->id }}">
                        <option value="Diproses" {{ $item->status === 'Diproses' ? 'selected' : '' }}>Diproses</option>
                        <option value="Selesai" {{ $item->status === 'Selesai' ? 'selected' : '' }}>Selesai</option>
                        <option value="Batal" {{ $item->status === 'Batal' ? 'selected' : '' }}>Batal</option>
                    </select>
                </td>
                <td class="text-center">
                    <button class="btn btn-danger btn-sm delete-btn" data-id="{{ $item->id }}" title="Hapus">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="12" class="text-center text-muted py-4">
                    Belum ada data antrian.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

@if($antrian->hasPages())
    <div class="d-flex justify-content-between align-items-center mt-3">
        <div class="text-muted small">
            Menampilkan {{ $antrian->firstItem() ?? 0 }} sampai {{ $antrian->lastItem() ?? 0 }} dari {{ $antrian->total() }} data
        </div>
        <div>
            {{ $antrian->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endif
