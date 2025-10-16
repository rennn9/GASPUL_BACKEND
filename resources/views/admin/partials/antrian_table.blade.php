<div id="antrian-table-container">
    <table class="table table-bordered table-striped table-hover">
        <thead class="table-light">
            <tr>
                <th>No.</th>
                <th>Nomor Antrian</th>
                <th>Nama</th>
                <th>No HP</th>
                <th>Alamat</th>
                <th>Bidang Layanan</th>
                <th>Layanan</th>
                <th>Tanggal Daftar</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($antrian as $item)
                @php
                    $statusClass = match($item->status) {
                        'Diproses' => 'bg-primary text-white',
                        'Selesai'  => 'bg-success text-white',
                        'Batal'    => 'bg-danger text-white',
                        default    => 'bg-primary text-white',
                    };
                @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->nomor_antrian }}</td>
                    <td>{{ $item->nama }}</td>
                    <td>{{ $item->no_hp }}</td>
                    <td>{{ $item->alamat }}</td>
                    <td>{{ $item->bidang_layanan }}</td>
                    <td>{{ $item->layanan }}</td>
                    <td>{{ \Carbon\Carbon::parse($item->tanggal_daftar)->translatedFormat('l, d/m/Y') }}</td>
                    <td>
                        <select class="form-select status-dropdown" data-id="{{ $item->id }}">
                            <option value="Diproses" {{ $item->status === 'Diproses' ? 'selected' : '' }} style="background-color:#ffc107;">
                                Diproses
                            </option>
                            <option value="Selesai" {{ $item->status === 'Selesai' ? 'selected' : '' }} style="background-color:#28a745; color:white;">
                                Selesai
                            </option>
                            <option value="Batal" {{ $item->status === 'Batal' ? 'selected' : '' }} style="background-color:#dc3545; color:white;">
                                Batal
                            </option>
                        </select>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">Belum ada data antrian.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Script untuk status dropdown dan refresh --}}
<script>
document.addEventListener("DOMContentLoaded", function () {

    // Fungsi untuk mengubah warna dropdown sesuai status
    function applyStatusColor(selectEl) {
        selectEl.classList.remove('bg-primary', 'bg-success', 'bg-danger', 'text-white');

        switch (selectEl.value) {
            case 'Diproses':
                selectEl.classList.add('bg-primary', 'text-white');
                break;
            case 'Selesai':
                selectEl.classList.add('bg-success', 'text-white');
                break;
            case 'Batal':
                selectEl.classList.add('bg-danger', 'text-white');
                break;
        }
    }

    // Fungsi untuk attach event ke semua dropdown status
    function attachDropdownEvents() {
        document.querySelectorAll('.status-dropdown').forEach(dropdown => {
            applyStatusColor(dropdown); // Pastikan warna sesuai status saat load

            dropdown.addEventListener('change', function () {
                const id = this.dataset.id;
                const status = this.value;

                applyStatusColor(this);

                fetch("{{ route('admin.antrian.updateStatus') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({ id, status })
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        alert("Gagal memperbarui status.");
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert("Terjadi kesalahan saat update status.");
                });
            });
        });
    }

    // === Refresh tabel antrian setiap 5 detik TANPA hilangkan pilihan & warna ===
    function refreshAntrianTable() {
        const statusMap = {};

        // Simpan semua pilihan & warna sebelum refresh
        document.querySelectorAll('.status-dropdown').forEach(select => {
            statusMap[select.dataset.id] = select.value;
        });

        fetch("{{ route('admin.antrian.table') }}")
            .then(res => res.text())
            .then(html => {
                const container = document.getElementById("antrian-table-container");
                container.innerHTML = html;

                // Restore status & warna setelah refresh
                document.querySelectorAll('.status-dropdown').forEach(select => {
                    const savedValue = statusMap[select.dataset.id];
                    if (savedValue) {
                        select.value = savedValue;
                    }
                    applyStatusColor(select);
                });

                // Re-attach event listener ke dropdown baru
                attachDropdownEvents();
            });
    }

    attachDropdownEvents();
    setInterval(refreshAntrianTable, 5000); // Refresh tiap 5 detik
});
</script>
</div>