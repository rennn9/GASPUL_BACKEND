@extends('layouts.app')

@section('content')
<h1>Tambah Pengaduan Pelayanan</h1>

<form action="{{ route('pengaduan-pelayanan.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <label>Nama</label>
    <input type="text" name="nama" required><br>

    <label>NIP</label>
    <input type="text" name="nip"><br>

    <label>Penjelasan</label>
    <textarea name="penjelasan" required></textarea><br>

    <label>File</label>
    <input type="file" name="file"><br>

    <button type="submit">Submit</button>
</form>
@endsection
