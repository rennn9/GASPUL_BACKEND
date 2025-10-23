@extends('admin.layout')

@section('content')
<h2 class="fw-bold">Daftar Antrian</h2>

{{-- ============================= --}}
{{-- TabBar Filter Tanggal Antrian --}}
{{-- ============================= --}}
<div class="mb-3">
    <ul class="nav nav-tabs" id="antrianTab" role="tablist">
        {{-- Tab Semua --}}
        <li class="nav-item">
            <button class="nav-link active" data-filter="all" type="button">Semua</button>
        </li>
        {{-- Tab Hari Ini --}}
        <li class="nav-item">
            <button class="nav-link" data-filter="today" type="button">Hari Ini</button>
        </li>
        {{-- Tab Besok --}}
        <li class="nav-item">
            <button class="nav-link" data-filter="tomorrow" type="button">Besok</button>
        </li>
        {{-- Tab Pilih Tanggal (custom) --}}
        <li class="nav-item">
            <input type="date" id="customDate" class="form-control" style="display:inline-block; width:auto;">
        </li>
    </ul>
</div>

<!-- Tombol Download PDF dengan Icon -->
<button id="download-pdf-btn" class="btn btn-success mb-3">
    <i class="bi bi-file-earmark-pdf"></i>
    <span id="download-pdf-label">Download Daftar Antrian Semua</span>
</button>

{{-- ============================= --}}
{{-- Container Tabel Antrian --}}
{{-- ============================= --}}
<div id="antrian-table-container">
    @include('admin.partials.antrian_table', ['antrian' => $antrian])
</div>

<script>
document.addEventListener("DOMContentLoaded", function(){

    // ===== Variabel global =====
    let currentFilter = 'all'; // default = semua
    let currentDate = null;    // hanya digunakan jika filter = 'custom'
    let currentPage = 1;       // halaman pagination saat ini

    const downloadBtn = document.getElementById('download-pdf-btn');

    // ===== Fungsi untuk update label tombol download =====
function updateDownloadButtonLabel() {
    let label = "Download Daftar Antrian ";
    switch (currentFilter) {
        case 'today': label += "Hari Ini"; break;
        case 'tomorrow': label += "Besok"; break;
        case 'custom': 
            if (currentDate) {
                const tanggal = new Date(currentDate);
                const tglFormatted = tanggal.toLocaleDateString('id-ID', {
                    day: '2-digit', month: 'long', year: 'numeric'
                });
                label += tglFormatted;
            } else {
                label += "(Pilih Tanggal)";
            }
            break;
        default: label += "Semua";
    }

    document.getElementById('download-pdf-label').textContent = label;
}


    // ===== Fungsi tombol download =====
    downloadBtn.onclick = function() {
        let url = "{{ route('admin.antrian.download.daftar') }}";
        url += "?filter=" + currentFilter;
        if (currentFilter === 'custom' && currentDate) {
            url += "&date=" + currentDate;
        }
        window.open(url, '_blank');
    };

    // ===== Fungsi untuk memberi warna pada dropdown status =====
    function applyStatusColor(selectEl){
        selectEl.classList.remove('bg-primary','bg-success','bg-danger','text-white');
        switch(selectEl.value){
            case 'Diproses': selectEl.classList.add('bg-primary','text-white'); break;
            case 'Selesai': selectEl.classList.add('bg-success','text-white'); break;
            case 'Batal': selectEl.classList.add('bg-danger','text-white'); break;
        }
    }

    // ===== Fungsi attach event pada dropdown status =====
    function attachDropdownEvents(){
        document.querySelectorAll('.status-dropdown').forEach(dropdown=>{
            applyStatusColor(dropdown);
            dropdown.onchange = function(){
                fetch("{{ route('admin.antrian.updateStatus') }}", {
                    method:"POST",
                    headers:{
                        "Content-Type":"application/json",
                        "X-CSRF-TOKEN":"{{ csrf_token() }}"
                    },
                    body: JSON.stringify({id:this.dataset.id, status:this.value})
                })
                .then(r => r.json())
                .then(d => { if(!d.success) alert("Gagal update status"); })
                .catch(e => { console.error(e); alert("Error update status"); });

                applyStatusColor(this);
            }
        });
    }

    // ===== Fungsi attach event pada pagination links =====
    function attachPaginationEvents(){
        document.querySelectorAll('.pagination a').forEach(link => {
            link.onclick = function(e){
                e.preventDefault();
                const url = new URL(this.href);
                currentPage = url.searchParams.get('page') || 1;
                refreshAntrianTable(false);
            }
        });
    }

    // ===== Fungsi refresh tabel antrian via AJAX =====
    function refreshAntrianTable(resetPage = true){
        if(resetPage) currentPage = 1;

        const statusMap = {};
        document.querySelectorAll('.status-dropdown').forEach(s => statusMap[s.dataset.id] = s.value);

        let url = "{{ route('admin.antrian.table') }}?filter=" + currentFilter + "&page=" + currentPage;
        if(currentFilter === 'custom' && currentDate) url += "&date=" + currentDate;

        fetch(url)
            .then(r => r.text())
            .then(html => {
                const container = document.getElementById("antrian-table-container");
                container.innerHTML = html;

                document.querySelectorAll('.status-dropdown').forEach(s => {
                    if(statusMap[s.dataset.id]) s.value = statusMap[s.dataset.id];
                    applyStatusColor(s);
                });

                attachDropdownEvents();
                attachPaginationEvents();
            });
    }

    // ===== Inisialisasi =====
    attachDropdownEvents();
    attachPaginationEvents();
    updateDownloadButtonLabel();
    setInterval(() => refreshAntrianTable(false), 5000);

    // ===== Event TabBar filter =====
    document.querySelectorAll('#antrianTab button[data-filter]').forEach(btn => {
        btn.onclick = function(){
            document.querySelectorAll('#antrianTab button[data-filter]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.dataset.filter;
            currentDate = null;
            updateDownloadButtonLabel();
            refreshAntrianTable(true);
        }
    });

    // ===== Event Custom Date Picker =====
    const customDate = document.getElementById('customDate');
    customDate.onchange = function(){
        document.querySelectorAll('#antrianTab button[data-filter]').forEach(b => b.classList.remove('active'));
        currentFilter = 'custom';
        currentDate = this.value;
        updateDownloadButtonLabel();
        refreshAntrianTable(true);
    }

});
</script>
@endsection
