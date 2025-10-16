@extends('admin.layout')

@section('content')
<div class="tab-content">

    <!-- Tab Antrian -->
    <div class="tab-pane fade show active" id="antrian" role="tabpanel">
        <h2 class="fw-bold">Daftar Antrian</h2>
        @include('admin.partials.antrian_table', ['antrian' => $antrian])
    </div>

</div>

{{-- Script untuk auto-switch tab berdasarkan hash (siap jika ada tab baru) --}}
<script>
document.addEventListener("DOMContentLoaded", function () {

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
