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

<!-- Form Tambah Status -->
@php
    $user = auth()->user();
    $role = $user->role;
    $statusLower = strtolower($statusText);

    $hideStatusForm = false;

    // === ATURAN BARU ===

    // 1. operator tidak boleh memberi status jika masih "sedang diproses"
    if ($statusLower == 'sedang diproses' && $role == 'operator') {
        $hideStatusForm = true;
    }

    // 2. operator_bidang tidak boleh memberi status jika status terakhir "perlu perbaikan"
    if ($statusLower == 'perlu perbaikan' && $role == 'operator_bidang') {
        $hideStatusForm = true;
    }

    // 3. status selesai/diproses tidak bisa diubah siapapun
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
        <input type="file" name="file_surat[]" class="form-control" multiple>
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

        radios.forEach(r => {
            r.addEventListener("change", () => {
                const val = r.value.toLowerCase();
                ketBox.style.display = (val === "ditolak" || val === "perlu perbaikan") ? "block" : "none";
                suratBox.style.display = (val === "diterima") ? "block" : "none";
                perbaikanBox.style.display = (val === "perbaikan selesai") ? "block" : "none";
            });
        });
    });
});
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
@endsection
