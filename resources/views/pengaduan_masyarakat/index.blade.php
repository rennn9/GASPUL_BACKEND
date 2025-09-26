@extends('admin.layout')

<h2>Daftar Pengaduan Masyarakat</h2>
<table>
    <thead>
        <tr>
            <th>Nama</th>
            <th>NIP</th>
            <th>Jenis Laporan</th>
            <th>Penjelasan</th>
            <th>File</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($pengaduanMasyarakat as $item)
            <tr>
                <td>{{ $item->nama }}</td>
                <td>{{ $item->nip }}</td>
                <td>{{ $item->jenis_laporan }}</td>
                <td>{{ $item->penjelasan }}</td>
                <td>
                    @if ($item->file)
                        <a href="{{ asset('storage/' . $item->file) }}" target="_blank">Lihat</a>
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
