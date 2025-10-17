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

{{-- ============================= --}}
{{-- Container Tabel Antrian --}}
{{-- Partial ini berisi tabel antrian dan status dropdown --}}
{{-- ============================= --}}
<div id="antrian-table-container">
    @include('admin.partials.antrian_table', ['antrian' => $antrian])
</div>

<script>
document.addEventListener("DOMContentLoaded", function(){

    // ===== Current filter & tanggal custom =====
    let currentFilter = 'all'; // default = semua
    let currentDate = null;    // hanya digunakan jika filter = 'custom'
    let currentPage = 1;       // halaman pagination saat ini

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
    // Setiap kali status berubah, lakukan AJAX update ke server
    function attachDropdownEvents(){
        document.querySelectorAll('.status-dropdown').forEach(dropdown=>{
            applyStatusColor(dropdown); // pastikan warna sesuai status saat load

            dropdown.onchange = function(){
                // Kirim request update status via AJAX
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

                applyStatusColor(this); // update warna langsung
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
                refreshAntrianTable(false); // false = tidak reset halaman
            }
        });
    }

    // ===== Fungsi refresh tabel antrian via AJAX =====
    // Menyimpan state dropdown agar tidak hilang saat refresh
    function refreshAntrianTable(resetPage = true){
        if(resetPage) currentPage = 1; // reset ke halaman 1 jika filter berubah

        const statusMap = {};

        // Simpan status saat ini
        document.querySelectorAll('.status-dropdown').forEach(s => statusMap[s.dataset.id] = s.value);

        // URL untuk fetch tabel (tambahkan filter, tanggal, dan page)
        let url = "{{ route('admin.antrian.table') }}?filter=" + currentFilter + "&page=" + currentPage;
        if(currentFilter === 'custom' && currentDate) url += "&date=" + currentDate;

        fetch(url)
            .then(r => r.text())
            .then(html => {
                // Ganti isi tabel dengan hasil baru
                const container = document.getElementById("antrian-table-container");
                container.innerHTML = html;

                // Restore status dropdown & warna
                document.querySelectorAll('.status-dropdown').forEach(s => {
                    if(statusMap[s.dataset.id]) s.value = statusMap[s.dataset.id];
                    applyStatusColor(s);
                });

                // Re-attach event ke dropdown baru
                attachDropdownEvents();

                // Re-attach event ke pagination baru
                attachPaginationEvents();
            });
    }

    // ===== Inisialisasi =====
    attachDropdownEvents();
    attachPaginationEvents();
    setInterval(() => refreshAntrianTable(false), 5000); // refresh tiap 5 detik tanpa reset page

    // ===== Event TabBar filter =====
    document.querySelectorAll('#antrianTab button[data-filter]').forEach(btn => {
        btn.onclick = function(){
            // Hilangkan active di semua tab
            document.querySelectorAll('#antrianTab button[data-filter]').forEach(b => b.classList.remove('active'));
            this.classList.add('active'); // set active di tab yang diklik

            currentFilter = this.dataset.filter;
            currentDate = null; // reset tanggal custom
            refreshAntrianTable(true); // true = reset ke halaman 1
        }
    });

    // ===== Event Custom Date Picker =====
    const customDate = document.getElementById('customDate');
    customDate.onchange = function(){
        // Hilangkan active di semua tab
        document.querySelectorAll('#antrianTab button[data-filter]').forEach(b => b.classList.remove('active'));

        currentFilter = 'custom';
        currentDate = this.value;
        refreshAntrianTable(true); // true = reset ke halaman 1
    }

});
</script>
@endsection
