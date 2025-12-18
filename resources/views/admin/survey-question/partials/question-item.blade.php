<div class="question-item card mb-3" data-question-id="{{ $question->id }}">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-grip-vertical drag-handle" style="cursor: move;"></i>
        <span class="badge bg-secondary">Pertanyaan {{ $question->urutan }}</span>
        @if($question->kode_unsur)
            <span class="badge bg-info">{{ $question->kode_unsur }}</span>
        @endif
        <div class="ms-auto">
            <button type="button" class="btn btn-sm btn-danger" onclick="deleteQuestion('{{ $question->id }}')">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-8">
                <label class="form-label">Pertanyaan <span class="text-danger">*</span></label>
                <textarea class="form-control question-text" rows="2" required>{{ $question->pertanyaan_text }}</textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label">Kode Unsur (opsional)</label>
                <input type="text" class="form-control kode-unsur" placeholder="U1, U2, dll" value="{{ $question->kode_unsur }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <div class="form-check">
                    <input class="form-check-input is-required" type="checkbox" {{ $question->is_required ? 'checked' : '' }}>
                    <label class="form-check-label">Wajib diisi</label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-check">
                    <input class="form-check-input is-text-input" type="checkbox" {{ $question->is_text_input ? 'checked' : '' }} onchange="toggleOptionsSection(this)">
                    <label class="form-check-label">Pertanyaan Terbuka (Text Input)</label>
                </div>
            </div>
        </div>
        <div class="options-section" style="display: {{ $question->is_text_input ? 'none' : 'block' }};">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label mb-0">Pilihan Jawaban</label>
                <button type="button" class="btn btn-sm btn-secondary" onclick="addOption('{{ $question->id }}')">
                    <i class="bi bi-plus"></i> Tambah Jawaban
                </button>
            </div>
            <div class="options-container">
                @foreach($question->options as $option)
                    <div class="option-item row mb-2 align-items-center" data-option-id="{{ $option->id }}">
                        <div class="col-md-6">
                            <input type="text" class="form-control option-text" placeholder="Jawaban {{ $option->urutan }}" value="{{ $option->jawaban_text }}" required>
                        </div>
                        <div class="col-md-3">
                            <input type="number" class="form-control option-poin" placeholder="Poin" value="{{ $option->poin }}" min="1" max="5" required>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteOption('{{ $option->id }}')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="mt-3">
            <button type="button" class="btn btn-primary btn-sm" onclick="saveQuestion('{{ $question->id }}')">
                <i class="bi bi-save"></i> Simpan Pertanyaan
            </button>
        </div>
    </div>
</div>
