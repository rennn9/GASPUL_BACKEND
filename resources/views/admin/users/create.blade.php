@extends('admin.layout')

@section('content')
<h2 class="fw-bold">Tambah User</h2>

<form action="{{ route('admin.users.store') }}" method="POST">
    @csrf

    <div class="mb-3">
        <label for="nip" class="form-label">NIP</label>
        <input type="text" name="nip" id="nip" class="form-control" value="{{ old('nip') }}" required>
        @error('nip')
            <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="name" class="form-label">Nama</label>
        <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
        @error('name')
            <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" name="password" id="password" class="form-control" required>
        @error('password')
            <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="password_confirmation" class="form-label">Konfirmasi Password</label>
        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
    </div>

    <div class="mb-3">
        <label for="role" class="form-label">Role</label>
        <select name="role" id="role" class="form-select" required>
            <option value="superadmin" {{ old('role') == 'superadmin' ? 'selected' : '' }}>Superadmin</option>
            <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
            <option value="operator" {{ old('role') == 'operator' ? 'selected' : '' }}>Operator</option>
            <option value="operator_bidang" {{ old('role') == 'operator_bidang' ? 'selected' : '' }}>Operator Bidang</option>
            <option value="user" {{ old('role') == 'user' ? 'selected' : '' }}>User</option>
        </select>
        @error('role')
            <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3" id="bidang-container" style="display: none;">
        <label for="bidang" class="form-label">Bidang</label>
        <select name="bidang" id="bidang" class="form-select">
            <option value="">-- Pilih Bidang --</option>
            <option value="Bagian Tata Usaha">Bagian Tata Usaha</option>
            <option value="Bidang Bimbingan Masyarakat Islam">Bidang Bimbingan Masyarakat Islam</option>
            <option value="Bidang Pendidikan Madrasah">Bidang Pendidikan Madrasah</option>
            <option value="Bimas Kristen">Bimas Kristen</option>
            <option value="Bimas Katolik">Bimas Katolik</option>
            <option value="Bimas Hindu">Bimas Hindu</option>
            <option value="Bimas Buddha">Bimas Buddha</option>
        </select>
        @error('bidang')
            <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>

    <button type="submit" class="btn btn-success">
        <i class="bi bi-plus-circle"></i> Tambah User
    </button>
    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Batal</a>
</form>

<script>
    const roleSelect = document.getElementById('role');
    const bidangContainer = document.getElementById('bidang-container');

    roleSelect.addEventListener('change', () => {
        if(roleSelect.value === 'operator_bidang') {
            bidangContainer.style.display = 'block';
        } else {
            bidangContainer.style.display = 'none';
        }
    });

    if(roleSelect.value === 'operator_bidang') {
        bidangContainer.style.display = 'block';
    }
</script>
@endsection
