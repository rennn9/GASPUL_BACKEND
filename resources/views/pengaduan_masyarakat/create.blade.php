@extends('layouts.app')

@section('content')
<h1>Tambah Pengaduan Masyarakat</h1>

<form action="{{ route('pengaduan-masyarakat.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <label>Nama</label>
    <input type="text" name="nama" required><br>

    <label>NIP</label>
    <input type="text" name="nip"><br>

    <label>Jenis Laporan</label>
    <input type="text" name="jenis_laporan" required><br>

    <label>Penjelasan</label>
    <textarea name="penjelasan" required></textarea><br>

    <label>File</label>
    <input type="file" name="file"><br>

    <button type="submit">Submit</button>
</form>
@endsection
