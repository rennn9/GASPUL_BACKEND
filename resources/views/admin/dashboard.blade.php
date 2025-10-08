@extends('admin.layout')

@section('content')
<div class="tab-content">
    <!-- Tab Masyarakat -->
    <div class="tab-pane fade show active" id="masyarakat" role="tabpanel">
        <h2 class="fw-bold">Daftar Pengaduan Masyarakat</h2>

        <div class="d-flex align-items-center mb-3">
            <label for="filterMasyarakat" class="me-2 fw-bold">Filter Waktu:</label>
            <select id="filterMasyarakat" class="form-select me-3" style="max-width:200px;">
                <option value="all" selected>Semua Data</option>
                <option value="week">Minggu Ini</option>
                <option value="month">Bulan Ini</option>
                <option value="year">Tahun Ini</option>
                <option value="last_year">Tahun Lalu</option>
            </select>
<form method="GET" action="{{ route('admin.pengaduan_masyarakat.pdf') }}" id="formPdfMasyarakat" target="_blank">
    <input type="hidden" name="filter" id="filterInputMasyarakat" value="all">
    <button type="submit" class="btn btn-success">
        <i class="bi bi-file-earmark-pdf"></i> Download PDF
    </button>
</form>

        </div>

        <div id="masyarakat-table-container">
            @include('admin.partials.masyarakat_table')
        </div>
    </div>

    <!-- Tab Pelayanan -->
    <div class="tab-pane fade" id="pelayanan" role="tabpanel">
        <h2 class="fw-bold">Daftar Pengaduan Pelayanan</h2>

        <div class="d-flex align-items-center mb-3">
            <label for="filterPelayanan" class="me-2 fw-bold">Filter Waktu:</label>
            <select id="filterPelayanan" class="form-select me-3" style="max-width:200px;">
                <option value="all" selected>Semua Data</option>
                <option value="week">Minggu Ini</option>
                <option value="month">Bulan Ini</option>
                <option value="year">Tahun Ini</option>
                <option value="last_year">Tahun Lalu</option>
            </select>
<form method="GET" action="{{ route('admin.pengaduan_pelayanan.pdf') }}" id="formPdfPelayanan" target="_blank">
    <input type="hidden" name="filter" id="filterInputPelayanan" value="all">
    <button type="submit" class="btn btn-success">
        <i class="bi bi-file-earmark-pdf"></i> Download PDF
    </button>
</form>

        </div>

        <div id="pelayanan-table-container">
            @include('admin.partials.pelayanan_table')
        </div>
    </div>
</div>

{{-- Script untuk filter + auto-switch tab + dynamic PDF --}}
<script>
document.addEventListener("DOMContentLoaded", function () {
// === Filter Masyarakat ===
filterMasyarakat.addEventListener("change", function () {
    const value = this.value;

    fetch("{{ route('admin.filter.masyarakat') }}?filter=" + value)
        .then(response => response.text())
        .then(html => {
            document.getElementById("masyarakat-table-container").innerHTML = html;
        });

    document.getElementById("filterInputMasyarakat").value = value;
});

// === Filter Pelayanan ===
filterPelayanan.addEventListener("change", function () {
    const value = this.value;

    fetch("{{ route('admin.filter.pelayanan') }}?filter=" + value)
        .then(response => response.text())
        .then(html => {
            document.getElementById("pelayanan-table-container").innerHTML = html;
        });

    document.getElementById("filterInputPelayanan").value = value;
});


    // === Script Tab dari Hash (sidebar) ===
    function showTabFromHash() {
        let hash = window.location.hash.split("&")[0];
        if (hash) {
            document.querySelectorAll(".tab-pane").forEach(tab => tab.classList.remove("show", "active"));
            let activeTab = document.querySelector(hash);
            if (activeTab) {
                activeTab.classList.add("show", "active");
            }
        }
    }

    showTabFromHash();
    window.addEventListener("hashchange", showTabFromHash);
});
</script>
@endsection
