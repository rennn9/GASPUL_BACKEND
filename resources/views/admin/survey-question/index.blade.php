@extends('admin.layout')

@section('content')
<div class="d-flex align-items-center mb-2">
    <a href="{{ route('admin.survey-templates.index') }}" class="btn btn-link text-dark p-0 me-2" title="Kembali ke Daftar Template">
        <i class="bi bi-arrow-left" style="font-size: 1.5rem;"></i>
    </a>
    <h2 class="fw-bold mb-0">Kelola Pertanyaan Survey</h2>
</div>
<p class="text-muted">{{ $template->nama }} (v{{ $template->versi }})</p>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h6 class="mb-1">{{ $template->nama }}</h6>
                <p class="mb-0 text-muted">{{ $template->deskripsi ?? 'Tidak ada deskripsi' }}</p>
            </div>
            <div>
                @if($template->is_active)
                    <span class="badge bg-success">AKTIF</span>
                @else
                    <span class="badge bg-secondary">Tidak Aktif</span>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Daftar Pertanyaan ({{ $questions->count() }})</h6>
        <button type="button" class="btn btn-sm btn-primary" onclick="addNewQuestion()">
            <i class="bi bi-plus-circle"></i> Tambah Pertanyaan
        </button>
    </div>
    <div class="card-body">
        <div id="questions-container" data-template-id="{{ $template->id }}">
            @forelse($questions as $question)
                @include('admin.survey-question.partials.question-item', ['question' => $question])
            @empty
                <div class="text-center text-muted py-4" id="empty-state">
                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                    <p>Belum ada pertanyaan. Klik tombol "Tambah Pertanyaan" untuk memulai.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

<!-- Floating Action Buttons - Berderet Horizontal -->
<div class="fab-container">
    <!-- FAB Preview Template (paling kiri dalam row) -->
    <a href="{{ route('admin.survey-templates.preview', $template->id) }}"
       class="btn btn-info fab-button fab-preview"
       title="Preview Template">
        <i class="bi bi-eye"></i>
    </a>

    <!-- FAB Simpan Semua (tengah) -->
    <button type="button"
            class="btn btn-success fab-button fab-save-all"
            onclick="saveAllQuestions()"
            title="Simpan Semua Pertanyaan">
        <i class="bi bi-save-fill"></i>
    </button>

    <!-- FAB Tambah Pertanyaan (paling kanan) -->
    <button type="button"
            class="btn btn-primary fab-button fab-add"
            onclick="addNewQuestion()"
            title="Tambah Pertanyaan">
        <i class="bi bi-plus-lg"></i>
    </button>
</div>

<!-- Lottie Loading Overlay -->
<div id="lottie-loading-overlay" style="display: none;">
    <div id="lottie-loading-container"></div>
</div>

<style>
.fab-container {
    position: fixed;
    bottom: 30px;
    right: 30px;
    display: flex;
    gap: 15px;
    z-index: 1000;
}

.fab-button {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    color: white;
    text-decoration: none;
}

.fab-button:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
    color: white;
}

.fab-button i {
    font-size: 24px;
}

.fab-preview {
    background-color: #17a2b8; /* info color */
}

.fab-save-all {
    background-color: #28a745; /* success color */
}

.fab-add {
    background-color: #007bff; /* primary color */
}

/* Lottie Loading Overlay - Fullscreen */
#lottie-loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background-color: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

#lottie-loading-container {
    width: 300px;
    height: 300px;
}
</style>

<!-- SortableJS CDN -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
const TEMPLATE_ID = {{ $template->id }};
let questionCounter = {{ $questions->count() }};

// Initialize sortable
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('questions-container');
    if (container) {
        new Sortable(container, {
            handle: '.drag-handle',
            animation: 150,
            onEnd: function() {
                updateQuestionOrders();
            }
        });
    }
});

// Add new question
function addNewQuestion() {
    const emptyState = document.getElementById('empty-state');
    if (emptyState) {
        emptyState.remove();
    }

    questionCounter++;
    const questionHtml = createQuestionHtml(null, questionCounter);
    document.getElementById('questions-container').insertAdjacentHTML('beforeend', questionHtml);
}

// Create question HTML
function createQuestionHtml(question, index) {
    const id = question ? question.id : `new-${index}`;
    const pertanyaan = question ? question.pertanyaan_text : '';
    const unsurPelayanan = question ? (question.unsur_pelayanan || '') : '';
    const kodeUnsur = question ? (question.kode_unsur || '') : `U${index}`;
    const isRequired = question ? question.is_required : true;
    const isTextInput = question ? question.is_text_input : false;
    const options = question ? question.options : [];

    return `
        <div class="question-item card mb-3" data-question-id="${id}">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-grip-vertical drag-handle" style="cursor: move;"></i>
                <span class="badge bg-secondary">Pertanyaan ${index}</span>
                ${kodeUnsur ? `<span class="badge bg-info">${kodeUnsur}</span>` : ''}
                <div class="ms-auto">
                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteQuestion('${id}')">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-8">
                        <label class="form-label fw-bold">Pertanyaan <span class="text-danger">*</span></label>
                        <textarea class="form-control question-text" rows="2" required placeholder="Tuliskan pertanyaan survey...">${pertanyaan}</textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Kode Unsur</label>
                        <input type="text" class="form-control bg-light kode-unsur" placeholder="Auto-generated" value="${kodeUnsur}" readonly>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Unsur Pelayanan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control unsur-pelayanan" required placeholder="Contoh: Persyaratan pelayanan, Prosedur pelayanan, dll" value="${unsurPelayanan}">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input is-required" type="checkbox" ${isRequired ? 'checked' : ''}>
                            <label class="form-check-label">Wajib diisi</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input is-text-input" type="checkbox" ${isTextInput ? 'checked' : ''} onchange="toggleOptionsSection(this)">
                            <label class="form-check-label">Pertanyaan Terbuka (Text Input)</label>
                        </div>
                    </div>
                </div>
                <div class="options-section" style="display: ${isTextInput ? 'none' : 'block'};">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label mb-0">Pilihan Jawaban</label>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="addOption('${id}')">
                            <i class="bi bi-plus"></i> Tambah Jawaban
                        </button>
                    </div>
                    <div class="options-container">
                        ${options.map((opt, idx) => createOptionHtml(opt, idx + 1)).join('')}
                    </div>
                </div>
                <div class="mt-3">
                    <button type="button" class="btn btn-primary btn-sm" onclick="saveQuestion('${id}')">
                        <i class="bi bi-save"></i> Simpan Pertanyaan
                    </button>
                </div>
            </div>
        </div>
    `;
}

// Create option HTML
function createOptionHtml(option, index) {
    const id = option ? option.id : `new-${Date.now()}-${index}`;
    const jawaban = option ? option.jawaban_text : '';
    const poin = option ? option.poin : index;

    return `
        <div class="option-item row mb-2 align-items-center" data-option-id="${id}">
            <div class="col-md-6">
                <input type="text" class="form-control option-text" placeholder="Jawaban ${index}" value="${jawaban}" required>
            </div>
            <div class="col-md-3">
                <input type="number" class="form-control option-poin" placeholder="Poin" value="${poin}" min="1" max="5" required>
            </div>
            <div class="col-md-3">
                ${option ? `
                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteOption('${id}')">
                        <i class="bi bi-trash"></i>
                    </button>
                ` : `
                    <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.option-item').remove()">
                        <i class="bi bi-trash"></i>
                    </button>
                `}
            </div>
        </div>
    `;
}

// Toggle options section
function toggleOptionsSection(checkbox) {
    const questionCard = checkbox.closest('.question-item');
    const optionsSection = questionCard.querySelector('.options-section');
    optionsSection.style.display = checkbox.checked ? 'none' : 'block';
}

// Add option
function addOption(questionId) {
    const questionCard = document.querySelector(`[data-question-id="${questionId}"]`);
    const optionsContainer = questionCard.querySelector('.options-container');
    const optionCount = optionsContainer.children.length + 1;
    optionsContainer.insertAdjacentHTML('beforeend', createOptionHtml(null, optionCount));
}

// Save question
async function saveQuestion(questionId) {
    const questionCard = document.querySelector(`[data-question-id="${questionId}"]`);
    const pertanyaan = questionCard.querySelector('.question-text').value.trim();
    const unsurPelayanan = questionCard.querySelector('.unsur-pelayanan').value.trim();
    const kodeUnsur = questionCard.querySelector('.kode-unsur').value.trim();
    const isRequired = questionCard.querySelector('.is-required').checked;
    const isTextInput = questionCard.querySelector('.is-text-input').checked;

    if (!pertanyaan) {
        alert('Pertanyaan harus diisi!');
        return;
    }

    if (!unsurPelayanan) {
        alert('Unsur Pelayanan harus diisi!');
        return;
    }

    const data = {
        survey_template_id: TEMPLATE_ID,
        pertanyaan_text: pertanyaan,
        unsur_pelayanan: unsurPelayanan,
        kode_unsur: kodeUnsur || null,
        is_required: isRequired,
        is_text_input: isTextInput,
        _token: '{{ csrf_token() }}'
    };

    try {
        const isNew = questionId.toString().startsWith('new-');
        const url = isNew
            ? '{{ route("admin.survey-questions.store") }}'
            : `/admin/survey-questions/${questionId}`;
        const method = isNew ? 'POST' : 'PUT';

        if (!isNew) {
            data._method = 'PUT';
        }

        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            if (isNew) {
                questionCard.dataset.questionId = result.data.id;
            }

            // Save options
            if (!isTextInput) {
                await saveAllOptions(result.data.id, questionCard);
            }

            alert('Pertanyaan berhasil disimpan!');
        } else {
            alert('Gagal menyimpan: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat menyimpan pertanyaan.');
    }
}

// Save all options
async function saveAllOptions(questionId, questionCard) {
    const optionItems = questionCard.querySelectorAll('.option-item');

    for (const optionItem of optionItems) {
        const optionId = optionItem.dataset.optionId;
        const jawabanText = optionItem.querySelector('.option-text').value.trim();
        const poin = parseInt(optionItem.querySelector('.option-poin').value);

        if (!jawabanText) continue;

        const data = {
            survey_question_id: questionId,
            jawaban_text: jawabanText,
            poin: poin,
            _token: '{{ csrf_token() }}'
        };

        const isNew = optionId.toString().startsWith('new-');
        const url = isNew
            ? '{{ route("admin.survey-options.store") }}'
            : `/admin/survey-options/${optionId}`;

        if (!isNew) {
            data._method = 'PUT';
        }

        await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(data)
        });
    }
}

// Save all questions at once
async function saveAllQuestions() {
    const questionCards = document.querySelectorAll('.question-item');

    if (questionCards.length === 0) {
        alert('Tidak ada pertanyaan untuk disimpan.');
        return;
    }

    let savedCount = 0;
    let errorCount = 0;
    const errors = [];

    // Confirm before saving all
    if (!confirm(`Simpan semua ${questionCards.length} pertanyaan?`)) {
        return;
    }

    // Disable button to prevent double-click
    const saveButton = document.querySelector('.fab-save-all');
    saveButton.disabled = true;

    // Show fullscreen Lottie loading overlay
    const overlay = document.getElementById('lottie-loading-overlay');
    overlay.style.display = 'flex';
    const lottieAnimation = lottie.loadAnimation({
        container: document.getElementById('lottie-loading-container'),
        renderer: 'svg',
        loop: true,
        autoplay: true,
        path: '/lottie/Speed.json'
    });

    // Save each question
    for (const questionCard of questionCards) {
        const questionId = questionCard.dataset.questionId;

        try {
            const pertanyaan = questionCard.querySelector('.question-text').value.trim();
            const unsurPelayanan = questionCard.querySelector('.unsur-pelayanan').value.trim();
            const kodeUnsur = questionCard.querySelector('.kode-unsur').value.trim();
            const isRequired = questionCard.querySelector('.is-required').checked;
            const isTextInput = questionCard.querySelector('.is-text-input').checked;

            // Validate
            if (!pertanyaan) {
                errors.push(`Pertanyaan ${questionCard.querySelector('.badge').textContent} harus diisi`);
                errorCount++;
                continue;
            }

            if (!unsurPelayanan) {
                errors.push(`Unsur Pelayanan ${questionCard.querySelector('.badge').textContent} harus diisi`);
                errorCount++;
                continue;
            }

            const data = {
                survey_template_id: TEMPLATE_ID,
                pertanyaan_text: pertanyaan,
                unsur_pelayanan: unsurPelayanan,
                kode_unsur: kodeUnsur || null,
                is_required: isRequired,
                is_text_input: isTextInput,
                _token: '{{ csrf_token() }}'
            };

            const isNew = questionId.toString().startsWith('new-');
            const url = isNew
                ? '{{ route("admin.survey-questions.store") }}'
                : `/admin/survey-questions/${questionId}`;

            if (!isNew) {
                data._method = 'PUT';
            }

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                if (isNew) {
                    questionCard.dataset.questionId = result.data.id;
                }

                // Save options
                if (!isTextInput) {
                    await saveAllOptions(result.data.id, questionCard);
                }

                savedCount++;
            } else {
                errors.push(`Error: ${result.message}`);
                errorCount++;
            }

        } catch (error) {
            console.error('Error:', error);
            errors.push(`Error saving question: ${error.message}`);
            errorCount++;
        }
    }

    // Hide overlay and destroy Lottie animation FIRST
    overlay.style.display = 'none';
    if (lottieAnimation) {
        lottieAnimation.destroy();
    }
    saveButton.disabled = false;

    // Show result AFTER overlay is hidden
    if (errorCount === 0) {
        alert(`✅ Berhasil menyimpan semua ${savedCount} pertanyaan!`);
        location.reload(); // Reload to refresh kode_unsur and urutan
    } else {
        alert(`⚠️ Selesai dengan ${savedCount} berhasil, ${errorCount} gagal.\n\nError:\n${errors.join('\n')}`);
    }
}

// Delete question
async function deleteQuestion(questionId) {
    if (questionId.toString().startsWith('new-')) {
        document.querySelector(`[data-question-id="${questionId}"]`).remove();
        return;
    }

    if (!confirm('Yakin ingin menghapus pertanyaan ini?')) {
        return;
    }

    try {
        const response = await fetch(`/admin/survey-questions/${questionId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        const result = await response.json();

        if (result.success) {
            document.querySelector(`[data-question-id="${questionId}"]`).remove();
            alert('Pertanyaan berhasil dihapus!');
        } else {
            alert('Gagal menghapus: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat menghapus pertanyaan.');
    }
}

// Delete option
async function deleteOption(optionId) {
    if (!confirm('Yakin ingin menghapus pilihan ini?')) {
        return;
    }

    try {
        const response = await fetch(`/admin/survey-options/${optionId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        const result = await response.json();

        if (result.success) {
            document.querySelector(`[data-option-id="${optionId}"]`).remove();
            alert('Pilihan berhasil dihapus!');
        } else {
            alert('Gagal menghapus: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat menghapus pilihan.');
    }
}

// Update question orders
async function updateQuestionOrders() {
    const questionCards = document.querySelectorAll('.question-item');
    const orders = [];

    questionCards.forEach((card, index) => {
        const questionId = card.dataset.questionId;
        if (!questionId.toString().startsWith('new-')) {
            orders.push({
                id: parseInt(questionId),
                urutan: index + 1
            });
        }
    });

    if (orders.length === 0) return;

    try {
        const response = await fetch('{{ route("admin.survey-questions.reorder") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ orders: orders })
        });

        const result = await response.json();
        if (!result.success) {
            alert('Gagal mengupdate urutan: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}
</script>
@endsection