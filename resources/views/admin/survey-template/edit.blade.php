@extends('admin.layout')

@section('content')
<h2 class="fw-bold">Edit Template Survey</h2>
<p class="text-muted">Edit metadata template (nama dan deskripsi)</p>

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.survey-templates.update', $surveyTemplate->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label">Versi</label>
                <input type="text"
                       class="form-control"
                       value="v{{ $surveyTemplate->versi }}"
                       disabled>
                <small class="text-muted">Versi tidak bisa diubah</small>
            </div>

            <div class="mb-3">
                <label for="nama" class="form-label">Nama Template <span class="text-danger">*</span></label>
                <input type="text"
                       class="form-control @error('nama') is-invalid @enderror"
                       id="nama"
                       name="nama"
                       value="{{ old('nama', $surveyTemplate->nama) }}"
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
                          rows="3">{{ old('deskripsi', $surveyTemplate->deskripsi) }}</textarea>
                @error('deskripsi')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror>
            </div>

            <div class="mb-3">
                <label class="form-label">Status</label>
                <input type="text"
                       class="form-control"
                       value="{{ $surveyTemplate->is_active ? 'AKTIF' : 'Tidak Aktif' }}"
                       disabled>
                <small class="text-muted">Status hanya bisa diubah dari halaman daftar template</small>
            </div>

            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i>
                <strong>Catatan:</strong> Untuk mengedit pertanyaan dan jawaban, gunakan menu "Kelola Pertanyaan" dari halaman daftar template.
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Simpan Perubahan
                </button>
                <a href="{{ route('admin.survey-templates.index') }}" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
