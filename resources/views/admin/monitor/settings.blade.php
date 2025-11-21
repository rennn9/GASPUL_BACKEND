@extends('admin.layout')

@section('content')
<div class="container mt-4">

    <h4 class="fw-bold mb-3">Pengaturan Monitor Antrian</h4>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form id="monitor-settings-form" action="{{ route('admin.monitor.settings.update') }}" method="POST">
        @csrf

        <div class="card shadow-sm mb-4">
            <div class="card-body">

                <h5 class="fw-bold mb-3">Video Monitor</h5>

                <label class="form-label fw-semibold">URL Video</label>
                <input type="text" name="video_url" id="video_url"
                       class="form-control @error('video_url') is-invalid @enderror"
                       value="{{ $settings->video_url ?? '' }}"
                       placeholder="Masukkan link YouTube / file MP4">

                @error('video_url')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror

                <!-- Preview Video -->
                <div class="mt-3" id="video-preview-container" style="display: none;">
                    <h6 class="fw-bold mb-2">Preview Video:</h6>
                    <div id="video-preview"></div>
                </div>

                <hr>

                <h5 class="fw-bold mb-3">Running Text</h5>

                <label class="form-label fw-semibold">Teks Berjalan</label>
                <textarea name="running_text" id="running_text"
                          class="form-control @error('running_text') is-invalid @enderror"
                          rows="2">{{ $settings->running_text ?? '' }}</textarea>

                @error('running_text')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror

            </div>
        </div>

        <button type="submit" class="btn btn-primary px-4">Simpan</button>

    </form>

    <form action="{{ route('admin.monitor.settings.reset') }}" method="POST" class="d-inline">
        @csrf
        <button class="btn btn-danger px-4">Reset Default</button>
    </form>
</div>

@endsection


@section('scripts')
<script>
/* ============================
   LIVE VIDEO PREVIEW
============================ */
function updateVideoPreview() {
    let url = document.getElementById("video_url").value;
    let preview = document.getElementById("video-preview");
    let container = document.getElementById("video-preview-container");

    if (!url) {
        container.style.display = "none";
        preview.innerHTML = "";
        return;
    }

    container.style.display = "block";

    // Cek apakah YouTube
    if (url.includes("youtube.com") || url.includes("youtu.be")) {
        let id = url.split("v=")[1] || url.split("/").pop();
        preview.innerHTML = `
            <iframe width="100%" height="300" src="https://www.youtube.com/embed/${id}" frameborder="0" allowfullscreen></iframe>
        `;
    }
    else {
        // MP4 / Direct video
        preview.innerHTML = `
            <video width="100%" height="300" controls>
                <source src="${url}" type="video/mp4">
            </video>
        `;
    }
}

document.getElementById("video_url").addEventListener("input", updateVideoPreview);
updateVideoPreview();

/* ============================
   AUTO SAVE (delay 1 detik)
============================ */
let typingTimer;
let delay = 1000;

document.querySelectorAll("#video_url, #running_text").forEach(el => {
    el.addEventListener("input", function() {
        clearTimeout(typingTimer);
        typingTimer = setTimeout(() => {
            autoSave();
        }, delay);
    });
});

function autoSave() {
    const data = {
        _token: "{{ csrf_token() }}",
        video_url: document.getElementById("video_url").value,
        running_text: document.getElementById("running_text").value,
    };

    fetch("{{ route('admin.monitor.settings.autosave') }}", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify(data)
    });
}
</script>
@endsection
