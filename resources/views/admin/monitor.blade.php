@extends('admin.layout')

@section('content')
<style>
html, body {
    height: 100%;
    margin: 0;
}

/* Container utama monitor */
#monitorContainer {
    display: flex;
    flex-direction: column;
    height: 100vh;
    transition: background-color 0.3s;
}

/* Tombol fullscreen di tengah atas */
#fullscreenContainer {
    display: flex;
    justify-content: center;
    margin-bottom: 0.5rem;
}

/* Info waktu & logo kementerian */
#infoCard {
    flex: 0 0 auto; /* tinggi tetap */
}
#infoCard .card-body {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 2rem;
}
#infoCard .left-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}
#infoCard .left-info img {
    height: 60px;
    width: auto;
}
#infoCard .left-info .text {
    display: flex;
    flex-direction: column;
    font-size: clamp(1rem, 2vw, 1.5rem);
    font-weight: bold;
}
#infoCard .right-info {
    font-size: clamp(1.5rem, 3vw, 2.5rem);
    font-weight: bold;
    text-align: right;
}

/* Row utama 3 kolom */
#monitorRow {
    flex: 1 1 0;
    display: flex;
    gap: 0.5rem;
}

/* Setiap kolom full height */
#monitorRow > .col-md-4 {
    display: flex;
    flex-direction: column;
}

/* Card full height tapi tidak melebihi parent */
#monitorRow > .col-md-4 > .card {
    display: flex;
    flex-direction: column;
    flex: 1 1 0;
    min-height: 0; /* penting agar scroll card-body bekerja */
}

/* Header & Footer */
.card-header,
.card-footer {
    flex: 0 0 auto;
    font-size: 2.2rem;
    font-weight: bold;
    padding: 1.5rem;
    text-align: center;
}

/* Body scrollable */
.card-body.overflow-auto {
    flex: 1 1 auto;
    min-height: 0;
    overflow-y: auto;
}

/* Footer “Antrian Dalam Proses” */
#count-proses {
    background-color: #e7f1ff;
    padding: 1rem;
    border-radius: 0.25rem;
    font-weight: 600;
    text-align: center;
}

/* Running text fullscreen */
#runningTextContainer {
    display: none; /* tampil hanya fullscreen */
    flex: 0 0 auto; /* setara card-footer */
    overflow: hidden;
    background-color: #ffc107;
    color: #000;
    font-size: clamp(1.2rem, 2vw, 2rem);
    font-weight: bold;
    white-space: nowrap;
    padding: 0.5rem 0;
}

/* Running text inline scroll */
#runningText {
    display: inline-block;
    animation: marquee 60s linear infinite;
}

@keyframes marquee {
    0%   { transform: translateX(100%); }
    100% { transform: translateX(-100%); }
}

/* Fullscreen mode */
.fullscreen-mode {
    background-color: #000;
    color: #fff;
}
</style>

<!-- Tombol Fullscreen (di tengah) -->
<div id="fullscreenContainer">
    <button id="btnFullscreen" class="btn btn-warning">
        <i class="bi bi-arrows-fullscreen"></i> Fullscreen
    </button>
</div>

<div id="monitorContainer">
    <!-- Info Hari, Tanggal, Waktu & Logo -->
    <div id="infoCard" class="card shadow-sm mb-2">
        <div class="card-body">
            <div class="left-info">
                <img src="{{ asset('assets/images/kemenag.png') }}" alt="Logo GASPUL">
                <div class="text">
                    <span>Kementerian Agama</span>
                    <span>Provinsi Sulawesi Barat</span>
                </div>
            </div>
            <div class="right-info" id="tanggal-waktu">--:--:--</div>
        </div>
    </div>

    <!-- Row 3 kolom -->
    <div id="monitorRow">
        <!-- Dalam Proses -->
        <div class="col-md-4">
            <div class="card border-primary shadow-sm w-100 d-flex flex-column">
                <div class="card-header bg-primary text-white">
                    Dalam Proses
                </div>
                <div class="card-body overflow-auto" id="dalam-proses" style="align-items: flex-start;">
                </div>
                <div class="card-footer" id="count-proses"></div>
            </div>
        </div>

        <!-- Nomor Antrian Sekarang -->
        <div class="col-md-4">
            <div class="card border-danger shadow-sm w-100 d-flex flex-column">
                <div class="card-header bg-danger text-white">
                    Nomor Antrian
                </div>
                <div class="card-body d-flex justify-content-center align-items-center" id="current-antrian"
                     style="font-size: clamp(5rem, 12vw, 8rem); font-weight:bold;">
                    -
                </div>
            </div>
        </div>

        <!-- Selesai -->
        <div class="col-md-4">
            <div class="card border-success shadow-sm w-100 d-flex flex-column">
                <div class="card-header bg-success text-white">
                    Selesai
                </div>
                <div class="card-body overflow-auto" id="selesai" style="align-items: flex-start;">
                </div>
            </div>
        </div>
    </div>

    <!-- Running Text Fullscreen -->
    <div id="runningTextContainer">
        <span id="runningText">
            Selamat datang di Sistem GASPUL Kementerian Agama Provinsi Sulawesi Barat! 
            Harap selalu menjaga ketertiban dan mengikuti petunjuk petugas. 
            Kami menghargai kesabaran Anda dan berharap pelayanan hari ini berjalan lancar. 
            Terima kasih telah menggunakan layanan kami dengan tertib.
        </span>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {

    /** Sesuaikan kecepatan running text berdasarkan panjang teks **/
    const runningText = document.getElementById("runningText");

    const adjustMarqueeSpeed = () => {
        // Hitung panjang teks (jumlah karakter)
        const textLength = runningText.textContent.length;

        // Rumus kecepatan: makin panjang teks → makin lama durasinya
        // Misal setiap 20 karakter = 1 detik (bisa kamu ubah sesuai preferensi)
        const duration = Math.max(10, textLength / 10);

        // Terapkan durasi animasi secara dinamis
        runningText.style.animationDuration = `${duration}s`;
    };

    // Jalankan saat pertama kali
    adjustMarqueeSpeed();

    // Jika nanti teks diubah secara dinamis
    const observer = new MutationObserver(adjustMarqueeSpeed);
    observer.observe(runningText, { childList: true, characterData: true, subtree: true });


    /** Update Hari, Tanggal & Waktu **/
    const updateDateTime = () => {
        const now = new Date();
        const optionsTanggal = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const tanggal = now.toLocaleDateString('id-ID', optionsTanggal);
        const waktu = now.toLocaleTimeString('id-ID', { hour12: false });
        document.getElementById("tanggal-waktu").textContent = `${tanggal} | ${waktu}`;
    };
    updateDateTime();
    setInterval(updateDateTime, 1000);

    /** Load Data Monitor Antrian **/
    const loadMonitorData = () => {
        fetch("{{ route('admin.monitor.data') }}")
            .then(res => res.json())
            .then(data => {
                const dalamProsesEl = document.getElementById("dalam-proses");
                dalamProsesEl.innerHTML = "";
                data.dalamProses.forEach(a => {
                    const container = document.createElement("div");
                    Object.assign(container.style, {
                        border: "1px solid #0d6efd",
                        borderRadius: "0.5rem",
                        margin: "0.25rem 0",
                        padding: "0.5rem",
                        width: "95%",
                        display: "flex",
                        justifyContent: "space-between",
                        alignItems: "center",
                        backgroundColor: "#e7f1ff"
                    });
                    container.innerHTML = `
                        <span style="font-size: clamp(2rem, 4vw, 3rem); font-weight:bold;">${a.nomor_antrian}</span>
                        <span style="font-size: clamp(1rem, 1.5vw, 1.2rem); color:#0d6efd;">${a.bidang_layanan}</span>
                    `;
                    dalamProsesEl.appendChild(container);
                });
                document.getElementById("count-proses").textContent = `${data.dalamProses.length} Antrian Dalam Proses`;

                const currentEl = document.getElementById("current-antrian");
                currentEl.textContent = data.current ? data.current.nomor_antrian : "-";

                const selesaiEl = document.getElementById("selesai");
                selesaiEl.innerHTML = "";
                data.selesai.forEach(a => {
                    const container = document.createElement("div");
                    Object.assign(container.style, {
                        border: "1px solid #198754",
                        borderRadius: "0.5rem",
                        margin: "0.25rem 0",
                        padding: "0.5rem",
                        width: "95%",
                        display: "flex",
                        justifyContent: "space-between",
                        alignItems: "center",
                        backgroundColor: "#dff6e3"
                    });
                    container.innerHTML = `
                        <span style="font-size: clamp(2rem, 4vw, 3rem); font-weight:bold;">${a.nomor_antrian}</span>
                        <span style="font-size: clamp(1rem, 1.5vw, 1.2rem); color:#198754;">${a.bidang_layanan}</span>
                    `;
                    selesaiEl.appendChild(container);
                });
            });
    };
    loadMonitorData();
    setInterval(loadMonitorData, 5000);

    /** Fullscreen Toggle **/
    const btnFullscreen = document.getElementById('btnFullscreen');
    const monitorContainer = document.getElementById('monitorContainer');
    const runningTextContainer = document.getElementById('runningTextContainer');

    btnFullscreen.addEventListener('click', () => {
        const isFullscreen = document.fullscreenElement;
        if (!isFullscreen) {
            monitorContainer.requestFullscreen();
        } else {
            document.exitFullscreen();
        }
    });

    document.addEventListener('fullscreenchange', () => {
        const isFullscreen = document.fullscreenElement;
        if (isFullscreen) {
            monitorContainer.classList.add('fullscreen-mode');
            runningTextContainer.style.display = 'flex';
        } else {
            monitorContainer.classList.remove('fullscreen-mode');
            runningTextContainer.style.display = 'none';
        }
    });
});
</script>
@endsection
