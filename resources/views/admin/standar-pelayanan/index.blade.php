@extends('admin.layout')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold">Standar Pelayanan</h3>

        <a href="{{ route('admin.standar-pelayanan.create') }}"
           class="btn btn-primary shadow-sm px-4">
            <i class="bi bi-plus-circle me-2"></i> Tambah Standar
        </a>
    </div>

    <!-- Card -->
    <div class="card shadow-sm border-0">
        <div class="card-body">

            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <div class="table-responsive mt-3">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="50">#</th>
                            <th>Bidang</th>
                            <th>Layanan</th>
                            <th>File</th>
                            <th width="130">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>{{ $item->bidang }}</td>
                            <td>{{ $item->layanan }}</td>

<td>
    @if($item->file_path)
        <a href="{{ asset('storage/' . $item->file_path) }}"
           target="_blank"
           class="btn btn-outline-primary btn-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i> View PDF
        </a>
    @else
        <span class="text-muted">Tidak ada file</span>
    @endif
</td>


                            <td class="text-center">
                                <a href="{{ route('admin.standar-pelayanan.edit', $item->id) }}"
                                   class="btn btn-warning btn-sm me-1">
                                    <i class="bi bi-pencil-square"></i>
                                </a>

                                <form class="d-inline"
                                      action="{{ route('admin.standar-pelayanan.destroy', $item->id) }}"
                                      method="POST"
                                      onsubmit="return confirm('Hapus data ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>

                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </div>

</div>

@endsection
