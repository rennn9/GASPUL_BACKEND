@extends('admin.layout')

@section('content')
<h2 class="fw-bold">Manajemen Template Survey</h2>
<p class="text-muted">Kelola template survey kepuasan masyarakat</p>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<a href="{{ route('admin.survey-templates.create') }}" class="btn btn-primary mb-3">
    <i class="bi bi-plus-circle"></i> Buat Template Baru
</a>

<div class="table-responsive">
    <table class="table table-bordered table-striped align-middle">
        <thead class="table-light text-center">
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 25%;">Nama Template</th>
                <th style="width: 30%;">Deskripsi</th>
                <th style="width: 8%;">Versi</th>
                <th style="width: 8%;">Status</th>
                <th style="width: 10%;">Dibuat Oleh</th>
                <th style="width: 14%;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($templates as $index => $template)
            <tr>
                <td class="text-center">
                    @if(method_exists($templates, 'firstItem'))
                        {{ $templates->firstItem() + $index }}
                    @else
                        {{ $index + 1 }}
                    @endif
                </td>
                <td>{{ $template->nama }}</td>
                <td>{{ $template->deskripsi ?? '-' }}</td>
                <td class="text-center">
                    <span class="badge bg-secondary">v{{ $template->versi }}</span>
                </td>
                <td class="text-center">
                    @if($template->is_active)
                        <span class="badge bg-success">AKTIF</span>
                    @else
                        <span class="badge bg-secondary">Tidak Aktif</span>
                    @endif
                </td>
                <td class="text-center">
                    {{ $template->createdBy ? $template->createdBy->name : '-' }}
                </td>
                <td class="text-center">
                    <div class="btn-group btn-group-sm" role="group">
                        <a href="{{ route('admin.survey-templates.preview', $template->id) }}"
                           class="btn btn-info"
                           title="Preview">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="{{ route('admin.survey-questions.index', $template->id) }}"
                           class="btn btn-primary"
                           title="Kelola Pertanyaan">
                            <i class="bi bi-list-ul"></i>
                        </a>
                        <a href="{{ route('admin.survey-templates.edit', $template->id) }}"
                           class="btn btn-warning"
                           title="Edit">
                            <i class="bi bi-pencil-square"></i>
                        </a>
                    </div>
                    <div class="btn-group btn-group-sm mt-1" role="group">
                        @if(!$template->is_active)
                        <form action="{{ route('admin.survey-templates.activate', $template->id) }}"
                              method="POST"
                              class="d-inline">
                            @csrf
                            <button class="btn btn-success"
                                    title="Aktifkan Template"
                                    onclick="return confirm('Aktifkan template ini? Template lain akan dinonaktifkan.')">
                                <i class="bi bi-check-circle"></i> Aktifkan
                            </button>
                        </form>
                        @endif
                        <form action="{{ route('admin.survey-templates.duplicate', $template->id) }}"
                              method="POST"
                              class="d-inline">
                            @csrf
                            <button class="btn btn-secondary"
                                    title="Duplikasi Template">
                                <i class="bi bi-files"></i> Duplikasi
                            </button>
                        </form>
                        @if(!$template->is_active)
                        <form action="{{ route('admin.survey-templates.destroy', $template->id) }}"
                              method="POST"
                              class="d-inline"
                              onsubmit="return confirm('Yakin ingin menghapus template ini?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger"
                                    title="Hapus">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center">Belum ada template survey.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if(method_exists($templates, 'links'))
    <div class="mt-3 d-flex justify-content-end">
        {{ $templates->links('pagination::bootstrap-5') }}
    </div>
@endif
@endsection
