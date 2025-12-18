@extends('admin.layout')

@section('content')
<h2 class="fw-bold">Preview Template Survey</h2>
<p class="text-muted">Preview template beserta pertanyaan dan jawaban</p>

<div class="card mb-3">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">{{ $template->nama }}</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p class="mb-1"><strong>Versi:</strong> v{{ $template->versi }}</p>
                <p class="mb-1"><strong>Status:</strong>
                    @if($template->is_active)
                        <span class="badge bg-success">AKTIF</span>
                    @else
                        <span class="badge bg-secondary">Tidak Aktif</span>
                    @endif
                </p>
            </div>
            <div class="col-md-6">
                <p class="mb-1"><strong>Deskripsi:</strong> {{ $template->deskripsi ?? '-' }}</p>
                <p class="mb-1"><strong>Total Pertanyaan:</strong> {{ $template->questions->count() }}</p>
            </div>
        </div>
    </div>
</div>

@if($template->questions->count() > 0)
<div class="card">
    <div class="card-header">
        <h6 class="mb-0">Daftar Pertanyaan</h6>
    </div>
    <div class="card-body">
        @foreach($template->questions as $index => $question)
        <div class="mb-4 pb-3 {{ $loop->last ? '' : 'border-bottom' }}">
            <div class="d-flex align-items-start">
                <div class="me-3">
                    <span class="badge bg-secondary">{{ $index + 1 }}</span>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="mb-1">{{ $question->pertanyaan_text }}</h6>
                            @if($question->kode_unsur)
                                <span class="badge bg-info">{{ $question->kode_unsur }}</span>
                            @endif
                            @if($question->is_required)
                                <span class="badge bg-warning text-dark">Wajib</span>
                            @endif
                            @if($question->is_text_input)
                                <span class="badge bg-success">Text Input</span>
                            @endif
                        </div>
                    </div>

                    @if(!$question->is_text_input && $question->options->count() > 0)
                        <div class="ms-3">
                            <p class="mb-1"><strong>Pilihan Jawaban:</strong></p>
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 10%;" class="text-center">Poin</th>
                                        <th>Jawaban</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($question->options as $option)
                                    <tr>
                                        <td class="text-center">
                                            <span class="badge bg-primary">{{ $option->poin }}</span>
                                        </td>
                                        <td>{{ $option->jawaban_text }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @elseif($question->is_text_input)
                        <div class="ms-3">
                            <p class="text-muted fst-italic">Pertanyaan terbuka (text input)</p>
                        </div>
                    @else
                        <div class="ms-3">
                            <p class="text-danger fst-italic">Belum ada pilihan jawaban</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@else
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle"></i>
    Template ini belum memiliki pertanyaan.
</div>
@endif

<div class="mt-3">
    <a href="{{ route('admin.survey-templates.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
    <a href="{{ route('admin.survey-questions.index', $template->id) }}" class="btn btn-primary">
        <i class="bi bi-pencil-square"></i> Kelola Pertanyaan
    </a>
</div>
@endsection
