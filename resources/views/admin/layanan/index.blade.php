@extends('admin.layout')

@section('content')
<div class="container py-4">

    <h3 class="mb-4 text-dark fw-bold">Daftar Layanan Publik</h3>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Filter Status --}}
    <div class="mb-3 d-flex align-items-center">
        <label for="statusFilter" class="me-2 fw-bold">Filter Status:</label>
        <select id="statusFilter" class="form-select" style="width: auto;">
            <option value="all" {{ !request('status') ? 'selected' : '' }}>Semua</option>
            <option value="sedang diproses" {{ request('status') == 'sedang diproses' ? 'selected' : '' }}>Sedang Diproses</option>
            <option value="menunggu verifikasi bidang" {{ request('status') == 'menunggu verifikasi bidang' ? 'selected' : '' }}>Menunggu Verifikasi Bidang</option>
            <option value="diterima" {{ request('status') == 'diterima' ? 'selected' : '' }}>Diterima</option>
            <option value="perlu perbaikan" {{ request('status') == 'perlu perbaikan' ? 'selected' : '' }}>Perlu Perbaikan</option>
            <option value="perbaikan selesai" {{ request('status') == 'perbaikan selesai' ? 'selected' : '' }}>Perbaikan Selesai</option>
            <option value="ditolak" {{ request('status') == 'ditolak' ? 'selected' : '' }}>Ditolak</option>
            <option value="selesai" {{ request('status') == 'selesai' ? 'selected' : '' }}>Selesai</option>
        </select>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-center">
                        <tr>
                            <th>No Registrasi</th>
                            <th>NIK</th>
                            <th>Nama</th>
                            <th>Bidang</th>
                            <th>Layanan</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $item)
                        @php
                            $lastStatus = $item->statusHistory->last();
                            $statusText = $lastStatus->status ?? 'Sedang Diproses';
                            $statusLower = strtolower($statusText);
                            $badgeClass = match($statusText) {
                                'Sedang Diproses' => 'bg-secondary',
                                'Menunggu Verifikasi Bidang' => 'bg-warning text-dark',
                                'Diterima' => 'bg-primary',
                                'Ditolak' => 'bg-danger',
                                'Perlu Perbaikan' => 'bg-warning text-dark',
                                'Perbaikan Selesai' => 'bg-info text-dark',
                                'Selesai' => 'bg-success',
                                default => 'bg-secondary'
                            };
                        @endphp

                        <tr class="text-center cursor-pointer"
                            data-bs-toggle="modal"
                            data-bs-target="#detailModal{{ $item->id }}">
                            <td>{{ $item->no_registrasi }}</td>
                            <td>{{ $item->nik }}</td>
                            <td>{{ $item->nama }}</td>
                            <td>{{ $item->bidang }}</td>
                            <td>{{ $item->layanan }}</td>
                            <td>
                                <span class="badge {{ $badgeClass }} px-3 py-2" style="font-size: .85rem;">
                                    {{ $statusText }}
                                </span>
                            </td>
                        </tr>

                        <!-- Modal Detail -->
                        <div class="modal fade" id="detailModal{{ $item->id }}" tabindex="-1">
                          <div class="modal-dialog modal-xl modal-dialog-centered">
                            <div class="modal-content">
                              <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title">Detail Pengajuan - {{ $item->no_registrasi }}</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                              </div>

                              <div class="modal-body">

                                <!-- Informasi -->
                                <div class="row mb-3">
                                    <div class="col-md-6"><strong>No Registrasi:</strong> {{ $item->no_registrasi }}</div>
                                    <div class="col-md-6"><strong>NIK:</strong> {{ $item->nik }}</div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6"><strong>Nama:</strong> {{ $item->nama }}</div>
                                    <div class="col-md-6"><strong>Email:</strong> {{ $item->email ?? '-' }}</div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6"><strong>Telepon:</strong> {{ $item->telepon ?? '-' }}</div>
                                    <div class="col-md-6"><strong>Bidang:</strong> {{ $item->bidang }}</div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-12"><strong>Layanan:</strong> {{ $item->layanan }}</div>
                                </div>

                                <!-- Berkas -->
                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <strong>Berkas:</strong>
                                        <div class="d-flex flex-wrap gap-2 mt-2">
                                            @if($item->berkas)
                                                @foreach(json_decode($item->berkas) as $file)
                                                    <a href="{{ asset('storage/'.$file) }}"
                                                       target="_blank"
                                                       class="btn btn-outline-secondary btn-sm text-truncate"
                                                       style="max-width:200px;">
                                                        <i class="bi bi-file-earmark-text me-1"></i>
                                                        {{ basename($file) }}
                                                    </a>
                                                @endforeach
                                            @else
                                                <span class="text-muted">Tidak ada berkas</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- History Status -->
                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <strong>History Status:</strong>
                                        <ul class="list-group mt-2">
                                            @forelse($item->statusHistory as $st)
                                                <li class="list-group-item d-flex justify-content-between">
                                                    <div>
                                                        <strong>{{ $st->status }}</strong>
                                                        @if($st->keterangan)
                                                            <br><small>Keterangan: {{ $st->keterangan }}</small>
                                                        @endif
                                                    </div>
                                                    <div class="text-end">
                                                        <small>{{ $st->user->name ?? 'Unknown' }} • {{ $st->created_at->format('d M Y H:i') }}</small>

                                                        <!-- File Surat -->
                                                        @if($st->file_surat)
                                                            <br>
                                                            @foreach(json_decode($st->file_surat) as $f)
                                                                <a href="{{ asset('storage/'.$f) }}" target="_blank" class="btn btn-outline-info btn-sm mt-1">
                                                                    {{ basename($f) }}
                                                                </a>
                                                            @endforeach
                                                        @endif

                                                        <!-- File Perbaikan -->
                                                        @if($st->file_perbaikan)
                                                            <br>
                                                            <a href="{{ asset('storage/'.$st->file_perbaikan) }}" target="_blank" class="btn btn-outline-success btn-sm mt-1">
                                                                {{ basename($st->file_perbaikan) }}
                                                            </a>
                                                        @endif
                                                    </div>
                                                </li>
                                            @empty
                                                <li class="list-group-item text-muted">Belum ada status</li>
                                            @endforelse
                                        </ul>
                                    </div>
                                </div>

<!-- Download Bukti Terima Section -->
@php
    $user = auth()->user();
    $role = $user->role;

    // Cek apakah sudah pernah ada status "Menunggu Verifikasi Bidang"
    $hasVerifikasiStatus = $item->statusHistory->contains(function($st) {
        return strtolower(trim($st->status)) === 'menunggu verifikasi bidang';
    });

    // Tombol download muncul jika:
    // 1. User role: superadmin, admin, atau operator
    // 2. Status sudah pernah "Menunggu Verifikasi Bidang" (atau status apapun setelahnya)
    $showDownloadButton = (
        in_array($role, ['superadmin', 'admin', 'operator']) &&
        $hasVerifikasiStatus
    );
@endphp

@if($showDownloadButton)
<div class="row mb-3">
    <div class="col-md-12">
        <div class="card border-info">
            <div class="card-body">
                <h6 class="card-title text-info mb-3">
                    <i class="bi bi-file-earmark-word me-2"></i>
                    Dokumen Bukti Terima Berkas
                </h6>
                <p class="card-text text-muted mb-3">
                    Download bukti terima berkas permohonan PTSP dalam format DOCX.
                </p>
                <a href="{{ route('admin.layanan.downloadBuktiTerima', $item->id) }}"
                   class="btn btn-info">
                    <i class="bi bi-download me-2"></i>Download Bukti Terima Berkas
                </a>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Tombol Kirim untuk Diverifikasi (Operator) -->
@php
    $statusLower = strtolower($statusText);

    // Tombol kirim hanya muncul jika:
    // 1. User adalah operator
    // 2. Status terakhir adalah "sedang diproses"
    // 3. Belum pernah dikirim (cek apakah ada status "menunggu verifikasi bidang")
    $sudahDikirim = $item->statusHistory->contains(function($st) {
        return strtolower(trim($st->status)) === 'menunggu verifikasi bidang';
    });

    $showKirimButton = (
        $role === 'operator' &&
        $statusLower === 'sedang diproses' &&
        !$sudahDikirim
    );
@endphp

@if($showKirimButton)
<div class="row mb-3">
    <div class="col-md-12">
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            <strong>Entri ini perlu dikirim untuk diverifikasi oleh bidang.</strong>
        </div>

        <form action="{{ route('admin.layanan.kirimVerifikasi', $item->id) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-primary"
                    onclick="return confirm('Apakah Anda yakin ingin mengirim entri ini untuk diverifikasi?')">
                <i class="bi bi-send me-2"></i>Kirim untuk Diverifikasi
            </button>
        </form>
    </div>
</div>
@endif

<!-- Form Tambah Status -->
@php
    $statusLower = strtolower($statusText);

    $hideStatusForm = false;

    // === ATURAN BARU ===

    // 0. Hanya operator dan operator_bidang yang boleh memberi status
    if (!in_array($role, ['operator', 'operator_bidang'])) {
        $hideStatusForm = true;
    }

    // 1. operator tidak boleh memberi status jika masih "sedang diproses"
    if ($statusLower == 'sedang diproses' && $role == 'operator') {
        $hideStatusForm = true;
    }

    // 1b. operator tidak boleh memberi status jika masih "menunggu verifikasi bidang"
    if ($statusLower == 'menunggu verifikasi bidang' && $role == 'operator') {
        $hideStatusForm = true;
    }

    // 2. operator_bidang tidak boleh memberi status jika BUKAN "menunggu verifikasi bidang"
    if ($statusLower !== 'menunggu verifikasi bidang' && $role == 'operator_bidang') {
        $hideStatusForm = true;
    }

    // 3. status selesai/ditolak tidak bisa diubah siapapun
    if (in_array($statusLower, ['selesai', 'ditolak'])) {
        $hideStatusForm = true;
    }
@endphp

@if(!$hideStatusForm)
<div class="row mb-3">
    <div class="col-md-12">

<form action="{{ route('admin.layanan.addStatus', $item->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="mb-3">
        <label class="form-label fw-bold">Pilih Status</label>
        <div class="d-flex flex-column gap-1">
            @if($role === 'operator_bidang')
                <label><input type="radio" name="status" value="Diterima"> Diterima</label>
                <label><input type="radio" name="status" value="Ditolak"> Ditolak</label>
                <label><input type="radio" name="status" value="Perlu Perbaikan"> Perlu Perbaikan</label>
            @endif

            @if($role === 'operator')
                @if($statusLower === 'diterima')
                    <label><input type="radio" name="status" value="Selesai"> Selesai</label>
                @endif
                @if($statusLower === 'perlu perbaikan')
                    <label><input type="radio" name="status" value="Perbaikan Selesai"> Perbaikan Selesai</label>
                @endif
            @endif
        </div>
    </div>

    <div class="mb-3" id="keteranganBox" style="display:none;">
        <label class="form-label fw-bold">Keterangan</label>
        <textarea name="keterangan" class="form-control" rows="2"></textarea>
    </div>

    <div class="mb-3" id="uploadSuratBox" style="display:none;">
        <label class="form-label fw-bold">Upload Surat Balasan</label>
        <input type="file" name="file_surat[]" id="fileSuratInput{{ $item->id }}" class="form-control" accept="application/pdf" multiple>
        <small class="text-muted">Format PDF, maksimal 5 MB per file.</small>
        <div id="fileSuratError{{ $item->id }}" class="alert alert-danger mt-2" style="display:none;" role="alert"></div>
    </div>

    <div class="mb-3" id="uploadPerbaikanBox" style="display:none;">
        <label class="form-label fw-bold">Upload File Perbaikan (PDF)</label>
        <input type="file" name="file_perbaikan" class="form-control" accept="application/pdf">
    </div>

    <button type="submit" class="btn btn-success">Simpan Status</button>
</form>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("detailModal{{ $item->id }}");
    modal.addEventListener('shown.bs.modal', function () {
        const radios = modal.querySelectorAll("input[name='status']");
        const ketBox = modal.querySelector("#keteranganBox");
        const suratBox = modal.querySelector("#uploadSuratBox");
        const perbaikanBox = modal.querySelector("#uploadPerbaikanBox");

        // Get file input and error div
        const fileSuratInput = modal.querySelector("#fileSuratInput{{ $item->id }}");
        const fileSuratError = modal.querySelector("#fileSuratError{{ $item->id }}");
        const form = modal.querySelector("form");

        radios.forEach(r => {
            r.addEventListener("change", () => {
                const val = r.value.toLowerCase();
                ketBox.style.display = (val === "ditolak" || val === "perlu perbaikan") ? "block" : "none";
                suratBox.style.display = (val === "diterima") ? "block" : "none";
                perbaikanBox.style.display = (val === "perbaikan selesai") ? "block" : "none";

                // Clear file input and errors when hiding
                if (val !== "diterima") {
                    if (fileSuratInput) {
                        fileSuratInput.value = '';
                        fileSuratError.style.display = 'none';
                    }
                }
            });
        });

        // File validation on change
        if (fileSuratInput) {
            fileSuratInput.addEventListener("change", function() {
                validateFileSurat(this, fileSuratError);
            });
        }

        // Form submission validation
        if (form) {
            form.addEventListener("submit", function(e) {
                const statusVal = modal.querySelector("input[name='status']:checked")?.value.toLowerCase();

                if (statusVal === "diterima" && fileSuratInput && fileSuratInput.files.length > 0) {
                    if (!validateFileSurat(fileSuratInput, fileSuratError)) {
                        e.preventDefault();
                        fileSuratError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        return false;
                    }
                }
            });
        }
    });
});

// Validation function for file surat
function validateFileSurat(input, errorDiv) {
    const files = input.files;
    const maxSize = 5 * 1024 * 1024; // 5 MB in bytes
    const allowedType = 'application/pdf';
    let errors = [];

    if (files.length === 0) {
        return true; // No files selected, allow (not required)
    }

    // Validate each file
    for (let i = 0; i < files.length; i++) {
        const file = files[i];

        // Check file type
        if (file.type !== allowedType && !file.name.toLowerCase().endsWith('.pdf')) {
            errors.push(`File "${file.name}" bukan format PDF. Hanya file PDF yang diperbolehkan.`);
        }

        // Check file size
        if (file.size > maxSize) {
            const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
            errors.push(`File "${file.name}" terlalu besar (${sizeMB} MB). Maksimal 5 MB per file.`);
        }
    }

    // Display errors or clear
    if (errors.length > 0) {
        errorDiv.innerHTML = '<strong>Kesalahan:</strong><ul class="mb-0">' +
            errors.map(err => `<li>${err}</li>`).join('') +
            '</ul>';
        errorDiv.style.display = 'block';
        return false;
    } else {
        errorDiv.style.display = 'none';
        return true;
    }
}
</script>

    </div>
</div>
@endif


                              </div>
                              <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                              </div>

                            </div>
                          </div>
                        </div>

                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Belum ada data layanan publik.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3 d-flex justify-content-end">
        {{ $data->links('pagination::bootstrap-5') }}
    </div>

</div>

<style>
.cursor-pointer { cursor: pointer; }
</style>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Filter status dropdown
    const statusFilter = document.getElementById('statusFilter');

    if (statusFilter) {
        statusFilter.onchange = function() {
            const filter = this.value;
            const url = new URL(window.location.href);

            if (filter === 'all') {
                url.searchParams.delete('status');
            } else {
                url.searchParams.set('status', filter);
            }

            // Reset to page 1 when filtering
            url.searchParams.delete('page');

            window.location.href = url.toString();
        }
    }
});
</script>

@endsection
