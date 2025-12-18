@extends('admin.layout')

@section('content')
<h2 class="fw-bold">Buat Template Survey Baru</h2>
<p class="text-muted">Buat template survey baru, kemudian tambahkan pertanyaan dan jawaban</p>

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.survey-templates.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="nama" class="form-label">Nama Template <span class="text-danger">*</span></label>
                <input type="text"
                       class="form-control @error('nama') is-invalid @enderror"
                       id="nama"
                       name="nama"
                       value="{{ old('nama') }}"
                       placeholder="Contoh: Template IKM 2025"
                       required>
                @error('nama')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="deskripsi" class="form-label">Deskripsi</label>
                <textarea class="form-control @error('deskripsi') is-invalid @enderror"
                          id="deskripsi"
                          name="deskripsi"
                          rows="3"
                          placeholder="Deskripsi template survey (opsional)">{{ old('deskripsi') }}</textarea>
                @error('deskripsi')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="text-muted">Versi akan ditentukan otomatis berdasarkan template terakhir</small>
            </div>

            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                <strong>Catatan:</strong>
                <ul class="mb-0 mt-2">
                    <li>Template baru akan dibuat dalam status <strong>Tidak Aktif</strong></li>
                    <li>Setelah dibuat, Anda akan diarahkan ke halaman kelola pertanyaan</li>
                    <li>Tambahkan minimal 1 pertanyaan sebelum mengaktifkan template</li>
                </ul>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Simpan & Lanjut ke Pertanyaan
                </button>
                <a href="{{ route('admin.survey-templates.index') }}" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
