<script>
document.addEventListener("DOMContentLoaded", () => {

    /** Sesuaikan kecepatan running text **/
    const runningText = document.getElementById("runningText");

    const adjustMarqueeSpeed = () => {
        const textLength = runningText.textContent.length;
        const duration = Math.max(10, textLength / 10);
        runningText.style.animationDuration = `${duration}s`;
    };

    adjustMarqueeSpeed();

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


    /** Load Data Monitor **/
    const loadMonitorData = () => {
        fetch("{{ route('admin.monitor.data') }}")
            .then(res => res.json())
            .then(data => {

                /** Dalam Proses **/
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

                document.getElementById("count-proses").textContent =
                    `${data.dalamProses.length} Antrian Dalam Proses`;


                /** NOMOR ANTRIAN SAAT INI **/
                const currentEl = document.getElementById("current-antrian");
                const currentNumber = data.current ? data.current.nomor_antrian : "-";
                currentEl.textContent = currentNumber;

                if (data.current && data.current.nomor_antrian) {
                    playAntrianAudio(data.current.nomor_antrian);
                }


                /** SELESAI **/
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

            })
            .catch(err => console.error("Gagal memuat data monitor:", err));
    };

    loadMonitorData();
    setInterval(loadMonitorData, 5000);


    /** Audio Antrian **/
    let lastPlayedNumber = null;

    const playAntrianAudio = (nomor) => {
        if (!nomor || nomor === lastPlayedNumber) return;

        const nomorInt = parseInt(nomor);
        if (isNaN(nomorInt) || nomorInt < 1 || nomorInt > 30) return;

        const nomorFormatted = nomorInt.toString().padStart(3, '0');
        const audioPath = `{{ asset('assets/audio') }}/${nomorFormatted}.mp3`;

        const audio = new Audio(audioPath);
        audio.play()
            .then(() => lastPlayedNumber = nomor)
            .catch(err => console.warn(`Tidak dapat memutar audio: ${nomorFormatted}.mp3`, err));
    };


    /** Fullscreen **/
    const btnFullscreen = document.getElementById('btnFullscreen');
    const monitorContainer = document.getElementById('monitorContainer');
    const runningTextContainer = document.getElementById('runningTextContainer');

    btnFullscreen.addEventListener('click', () => {
        if (!document.fullscreenElement) {
            monitorContainer.requestFullscreen();
        } else {
            document.exitFullscreen();
        }
    });

    document.addEventListener('fullscreenchange', () => {
        if (document.fullscreenElement) {
            monitorContainer.classList.add('fullscreen-mode');
            runningTextContainer.style.display = 'flex';
        } else {
            monitorContainer.classList.remove('fullscreen-mode');
            runningTextContainer.style.display = 'none';
        }
    });
});
</script>
