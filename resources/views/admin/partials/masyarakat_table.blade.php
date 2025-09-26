    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Nama</th>
                <th>NIP</th>
                <th>Jenis Laporan</th>
                <th>Penjelasan</th>
                <th>File</th>
                <th>Tanggal</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pengaduanMasyarakat as $item)
                <tr>
                    <td>{{ $item->nama }}</td>
                    <td>{{ $item->nip }}</td>
                    <td>{{ $item->jenis_laporan }}</td>
                    <td>{{ $item->penjelasan }}</td>
                    <td>
                        @if($item->file)
                            <a href="{{ asset('storage/'.$item->file) }}" target="_blank">Lihat</a>
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ $item->created_at->format('d-m-Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center">Tidak ada data</td></tr>
            @endforelse
        </tbody>
    </table>
