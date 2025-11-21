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
    flex: 0 0 auto;
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
    display: flex;
    align-items: center;
    gap: 1.5rem;
    font-size: clamp(1.5rem, 3vw, 2.5rem);
    font-weight: bold;
    text-align: right;
}
#infoCard .right-info .logo-gaspul {
    height: 60px;
    width: auto;
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

/* Card full height */
#monitorRow > .col-md-4 > .card {
    display: flex;
    flex-direction: column;
    flex: 1 1 0;
    min-height: 0;
}

/* Header dan footer */
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
    display: none;
    flex: 0 0 auto;
    overflow: hidden;
    background-color: #ffc107;
    color: #000;
    font-size: clamp(1.2rem, 2vw, 2rem);
    font-weight: bold;
    white-space: nowrap;
    padding: 0.5rem 0;
}

/* Running text inner */
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
