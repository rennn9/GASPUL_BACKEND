@extends('admin.layout')

@section('content')
<h2 class="fw-bold">Manajemen User</h2>

<a href="{{ route('admin.users.create') }}" class="btn btn-primary mb-3">
    <i class="bi bi-plus-circle"></i> Tambah User
</a>

<table class="table table-bordered table-striped">
    <thead class="table-light">
        <tr>
            <th>No</th>
            <th>NIP</th>
            <th>Nama</th>
            <th>Role</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        @forelse($users as $index => $user)
        <tr>
            <td>
                @if(method_exists($users, 'firstItem'))
                    {{ $users->firstItem() + $index }}
                @else
                    {{ $index + 1 }}
                @endif
            </td>
            <td>{{ $user->nip }}</td>
            <td>{{ $user->name }}</td>
            <td>{{ ucfirst($user->role) }}</td>
            <td>
                <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-sm btn-warning">
                    <i class="bi bi-pencil-square"></i> Edit
                </a>
                <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus user ini?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-danger">
                        <i class="bi bi-trash"></i> Hapus
                    </button>
                </form>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="5" class="text-center">Belum ada user.</td>
        </tr>
        @endforelse
    </tbody>
</table>

@if(method_exists($users, 'links'))
    {{ $users->links('pagination::bootstrap-5') }}
@endif
@endsection
