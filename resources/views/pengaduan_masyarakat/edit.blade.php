@extends('layouts.app')

@section('content')
<h1>Edit Pengaduan Masyarakat</h1>

<form action="{{ route('pengaduan-masyarakat.update', $pengaduan_masyarakat->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <label>Nama</label>
    <input type="text" name="nama" value="{{ $pengaduan_masyarakat->nama }}" required><br>

    <label>NIP</label>
    <input type="text" name="nip" value="{{ $pengaduan_masyarakat->nip }}"><br>

    <label>Jenis Laporan</label>
    <input type="text" name="jenis_laporan" value="{{ $pengaduan_masyarakat->jenis_laporan }}" required><br>

    <label>Penjelasan</label>
    <textarea name="penjelasan" required>{{ $pengaduan_masyarakat->penjelasan }}</textarea><br>

    <label>File</label>
    <input type="file" name="file"><br>
    @if($pengaduan_masyarakat->file)
        File saat ini: <a href="{{ asset('storage/'.$pengaduan_masyarakat->file) }}" target="_blank">Download</a>
    @endif

    <button type="submit">Update</button>
</form>
@endsection
