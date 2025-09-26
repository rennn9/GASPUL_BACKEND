@extends('admin.layout')

<h2>Daftar Pengaduan Pelayanan</h2>
<table>
    <thead>
        <tr>
            <th>Nama</th>
            <th>NIP</th>
            <th>Penjelasan</th>
            <th>File</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($pengaduanPelayanan as $item)
            <tr>
                <td>{{ $item->nama }}</td>
                <td>{{ $item->nip }}</td>
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
