@extends('layouts.app')

@section('content')
<h1>Edit Pengaduan Pelayanan</h1>

<form action="{{ route('pengaduan-pelayanan.update', $pengaduan_pelayanan->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <label>Nama</label>
    <input type="text" name="nama" value="{{ $pengaduan_pelayanan->nama }}" required><br>

    <label>NIP</label>
    <input type="text" name="nip" value="{{ $pengaduan_pelayanan->nip }}"><br>

    <label>Penjelasan</label>
    <textarea name="penjelasan" required>{{ $pengaduan_pelayanan->penjelasan }}</textarea><br>

    <label>File</label>
    <input type="file" name="file"><br>
    @if($pengaduan_pelayanan->file)
        File saat ini: <a href="{{ asset('storage/'.$pengaduan_pelayanan->file) }}" target="_blank">Download</a>
    @endif

    <button type="submit">Update</button>
</form>
@endsection
