@extends('admin.layout')

@section('content')
    <h2 class="mb-3">Daftar Pengaduan Masyarakat</h2>

    <!-- Filter -->
    <form method="GET" action="{{ route('admin.masyarakat') }}" class="d-flex align-items-center mb-3">
        <label class="me-2">Filter Berdasarkan Waktu:</label>
        <select name="filter" onchange="this.form.submit()" class="form-select w-auto">
            <option value="">Semua Data</option>
            <option value="week" {{ request('filter') == 'week' ? 'selected' : '' }}>Minggu Ini</option>
            <option value="month" {{ request('filter') == 'month' ? 'selected' : '' }}>Bulan Ini</option>
            <option value="year" {{ request('filter') == 'year' ? 'selected' : '' }}>Tahun Ini</option>
            <option value="last_year" {{ request('filter') == 'last_year' ? 'selected' : '' }}>Tahun Lalu</option>
        </select>
        <a href="{{ route('admin.pengaduan_masyarakat.pdf', ['filter' => request('filter')]) }}" class="btn btn-success ms-3">
            Download PDF
        </a>
    </form>

    <!-- Tabel -->
    <form action="{{ route('admin.multi_delete') }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus data yang dipilih?')">
        @csrf
        @method('DELETE')
        <input type="hidden" name="type" value="masyarakat">

        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th><input type="checkbox" id="select-all"></th>
                    <th>Nama</th>
                    <th>NIP</th>
                    <th>Jenis Laporan</th>
                    <th>Penjelasan</th>
                    <th>File</th>
                    <th>Tanggal Masuk</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pengaduanMasyarakat as $item)
                <tr>
                    <td><input type="checkbox" name="ids[]" value="{{ $item->id }}"></td>
                    <td>{{ $item->nama }}</td>
                    <td>{{ $item->nip }}</td>
                    <td>{{ $item->jenis_laporan }}</td>
                    <td>{{ $item->penjelasan }}</td>
                    <td>
                        @if($item->file)
                            <a href="{{ asset('storage/' . $item->file) }}" target="_blank">Lihat File</a>
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ $item->created_at->format('d-m-Y H:i') }}</td>
                    <td>
                        <form action="{{ route('admin.delete', $item->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus data ini?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-danger">🗑 Hapus</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center">Tidak ada data tersedia</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <button type="submit" class="btn btn-danger">Hapus yang Dipilih</button>
    </form>

    <script>
        document.getElementById('select-all').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="ids[]"]');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });
    </script>
@endsection
