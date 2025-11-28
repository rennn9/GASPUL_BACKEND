@extends('admin.layout')

@section('content')
<div class="container py-4">

    <h3 class="mb-4 text-dark fw-bold">Daftar Layanan Publik</h3>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-center">
                        <tr>
                            <th>NIK</th>
                            <th>No Registrasi</th>
                            <th>Bidang</th>
                            <th>Layanan</th>
                            <th>Berkas</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $item)
                        <tr class="text-center">
                            <td>{{ $item->nik }}</td>
                            <td>{{ $item->no_registrasi }}</td>
                            <td>{{ $item->bidang }}</td>
                            <td>{{ $item->layanan }}</td>
                            @php
    $colors = ['primary', 'success', 'info', 'warning', 'danger', 'secondary']; // warna bootstrap
@endphp

<td class="text-center">
    @if($item->berkas)
        <div class="d-flex justify-content-center flex-wrap gap-1">
            @foreach(json_decode($item->berkas, true) as $index => $file)
                @php
                    $color = $colors[$index % count($colors)];
                @endphp
                <button type="button" class="btn btn-sm btn-outline-{{ $color }} text-truncate" 
                    style="max-width: 180px;" 
                    data-bs-toggle="modal" 
                    data-bs-target="#fileModal{{ $item->id }}{{ $index }}"
                    title="{{ basename($file) }}">
                    <i class="bi bi-file-earmark-text me-1"></i>{{ basename($file) }}
                </button>

                <!-- Modal -->
                <div class="modal fade" id="fileModal{{ $item->id }}{{ $index }}" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title">{{ basename($file) }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <iframe src="{{ asset('storage/' . $file) }}" frameborder="0" style="width:100%;height:500px;"></iframe>
                      </div>
                      <div class="modal-footer">
                        <a href="{{ asset('storage/' . $file) }}" class="btn btn-primary" download>Download</a>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                      </div>
                    </div>
                  </div>
                </div>
            @endforeach
        </div>
    @else
        <span class="text-muted">Tidak ada berkas</span>
    @endif
</td>

                            <td>{{ $item->created_at->format('d M Y - H:i') }}</td>
                            <td class="d-flex justify-content-center gap-1">
                                <form action="{{ route('admin.layanan.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Apakah yakin ingin menghapus data ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Belum ada data layanan publik.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3 d-flex justify-content-end">
        {{ $data->links('pagination::bootstrap-5') }}
    </div>

</div>
@endsection
