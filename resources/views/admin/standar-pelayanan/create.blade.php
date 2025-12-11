@extends('admin.layout')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0">Tambah Standar Pelayanan</h4>
        <a href="{{ route('admin.standar-pelayanan.index') }}" class="btn btn-secondary btn-sm">Kembali</a>
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

            <form action="{{ route('admin.standar-pelayanan.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                {{-- Dropdown Bidang --}}
                <div class="mb-3">
                    <label class="form-label fw-bold">Bidang</label>
                    <select id="selectBidang" name="bidang" class="form-select" required>
                        <option value="">-- Pilih Bidang --</option>
                        @foreach($layananPerBidang as $bidang => $layanans)
                            <option value="{{ $bidang }}">{{ $bidang }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Dropdown Layanan --}}
                <div class="mb-3">
                    <label class="form-label fw-bold">Layanan</label>
                    <select id="selectLayanan" name="layanan" class="form-select" required disabled>
                        <option value="">-- Pilih Layanan --</option>
                    </select>
                </div>

                {{-- File Upload --}}
                <div class="mb-3">
                    <label class="form-label fw-bold">Upload File (PDF)</label>
                    <input type="file" name="file" class="form-control" accept="application/pdf">
                    <small class="text-muted">Format PDF, maksimal 5 MB.</small>
                </div>

                <button type="submit" class="btn btn-primary px-4">
                    Simpan
                </button>
            </form>

        </div>
    </div>

</div>

{{-- SCRIPT untuk dynamic dropdown --}}
<script>
    const layananData = @json($layananPerBidang);

    const bidangSelect = document.getElementById('selectBidang');
    const layananSelect = document.getElementById('selectLayanan');

    bidangSelect.addEventListener('change', function () {
        const bidang = this.value;

        // Reset layanan dropdown
        layananSelect.innerHTML = '<option value="">-- Pilih Layanan --</option>';

        if (bidang && layananData[bidang]) {
            layananData[bidang].forEach(l => {
                const option = document.createElement('option');
                option.value = l;
                option.textContent = l;
                layananSelect.appendChild(option);
            });

            layananSelect.disabled = false;
        } else {
            layananSelect.disabled = true;
        }
    });
</script>

@endsection
