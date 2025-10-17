<table class="table table-bordered table-striped table-hover">
    <thead class="table-light">
        <tr>
            <th>No.</th>
            <th>Nomor Antrian</th>
            <th>Nama</th>
            <th>No HP</th>
            <th>Alamat</th>
            <th>Bidang Layanan</th>
            <th>Layanan</th>
            <th>Tanggal Daftar</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($antrian as $item)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $item->nomor_antrian }}</td>
            <td>{{ $item->nama }}</td>
            <td>{{ $item->no_hp }}</td>
            <td>{{ $item->alamat }}</td>
            <td>{{ $item->bidang_layanan }}</td>
            <td>{{ $item->layanan }}</td>
            <td>{{ \Carbon\Carbon::parse($item->tanggal_daftar)->translatedFormat('l, d/m/Y') }}</td>
            <td>
                <select class="form-select status-dropdown" data-id="{{ $item->id }}">
                    <option value="Diproses" {{ $item->status==='Diproses'?'selected':'' }}>Diproses</option>
                    <option value="Selesai" {{ $item->status==='Selesai'?'selected':'' }}>Selesai</option>
                    <option value="Batal" {{ $item->status==='Batal'?'selected':'' }}>Batal</option>
                </select>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="9" class="text-center text-muted py-4">Belum ada data antrian.</td>
        </tr>
        @endforelse
    </tbody>
</table>
