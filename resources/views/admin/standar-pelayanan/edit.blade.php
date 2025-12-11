@extends('admin.layout')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0">Edit Standar Pelayanan</h4>
        <a href="{{ route('admin.standar-pelayanan.index') }}" class="btn btn-secondary btn-sm">
            Kembali
        </a>
    </div>

    {{-- ALERT: Berhasil --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Berhasil!</strong> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ALERT: Gagal --}}
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Gagal!</strong> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ALERT: Validasi --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Terjadi kesalahan:</strong>
            <ul class="mb-0">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif


    <div class="card shadow-sm">
        <div class="card-body">

            {{-- Gunakan route store, karena updateOrCreate dipakai di controller --}}
            <form action="{{ route('admin.standar-pelayanan.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                {{-- Bidang --}}
                <div class="mb-3">
                    <label class="form-label fw-bold">Bidang</label>
                    <input type="text" name="bidang" class="form-control" value="{{ $item->bidang }}" required>
                </div>

                {{-- Layanan --}}
                <div class="mb-3">
                    <label class="form-label fw-bold">Layanan</label>
                    <input type="text" name="layanan" class="form-control" value="{{ $item->layanan }}" required>
                </div>

                {{-- File Upload --}}
                <div class="mb-3">
                    <label class="form-label fw-bold">File PDF</label>

                    @if ($item->file_path)
                        <p class="mb-1">
                            <a href="{{ $item->file_url }}" target="_blank" class="text-primary">
                                Lihat file saat ini
                            </a>
                        </p>
                    @endif

                    <input type="file" name="file" class="form-control" accept="application/pdf">
                    <small class="text-muted">Upload hanya jika ingin mengganti file.</small>
                </div>

                <button type="submit" class="btn btn-primary">
                    Update
                </button>
            </form>
        </div>
    </div>

</div>
@endsection
