@extends('admin.layout')

@section('content')
<h2 class="fw-bold">Edit User</h2>

<form action="{{ route('admin.users.update', $user->id) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="mb-3">
        <label for="nip" class="form-label">NIP</label>
        <input type="text" class="form-control" id="nip" name="nip" value="{{ old('nip', $user->nip) }}" required>
    </div>

    <div class="mb-3">
        <label for="name" class="form-label">Nama</label>
        <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $user->name) }}" required>
    </div>

    <div class="mb-3">
        <label for="no_hp" class="form-label">No. HP</label>
        <input type="text" class="form-control" id="no_hp" name="no_hp" value="{{ old('no_hp', $user->no_hp) }}" placeholder="Contoh: 08123456789">
    </div>

    <div class="mb-3">
        <label for="password" class="form-label">Password (kosongkan jika tidak ingin diubah)</label>
        <input type="password" class="form-control" id="password" name="password">
    </div>

    <div class="mb-3">
        <label for="password_confirmation" class="form-label">Konfirmasi Password</label>
        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
    </div>

    <div class="mb-3">
        <label for="role" class="form-label">Role</label>
        <select class="form-select" id="role" name="role" required>
            <option value="superadmin" {{ $user->role === 'superadmin' ? 'selected' : '' }}>Superadmin</option>
            <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Admin</option>
            <option value="operator" {{ $user->role === 'operator' ? 'selected' : '' }}>Operator</option>
            <option value="operator_bidang" {{ $user->role === 'operator_bidang' ? 'selected' : '' }}>Operator Bidang</option>
            <option value="user" {{ $user->role === 'user' ? 'selected' : '' }}>User</option>
        </select>
    </div>

    <div class="mb-3" id="bidang-container" style="display: none;">
        <label for="bidang" class="form-label">Bidang</label>
        <select name="bidang" id="bidang" class="form-select">
            <option value="">-- Pilih Bidang --</option>
            <option value="Bagian Tata Usaha" {{ $user->bidang === 'Bagian Tata Usaha' ? 'selected' : '' }}>Bagian Tata Usaha</option>
            <option value="Bidang Bimbingan Masyarakat Islam" {{ $user->bidang === 'Bidang Bimbingan Masyarakat Islam' ? 'selected' : '' }}>Bidang Bimbingan Masyarakat Islam</option>
            <option value="Bidang Pendidikan Madrasah" {{ $user->bidang === 'Bidang Pendidikan Madrasah' ? 'selected' : '' }}>Bidang Pendidikan Madrasah</option>
            <option value="Bimas Kristen" {{ $user->bidang === 'Bimas Kristen' ? 'selected' : '' }}>Bimas Kristen</option>
            <option value="Bimas Katolik" {{ $user->bidang === 'Bimas Katolik' ? 'selected' : '' }}>Bimas Katolik</option>
            <option value="Bimas Hindu" {{ $user->bidang === 'Bimas Hindu' ? 'selected' : '' }}>Bimas Hindu</option>
            <option value="Bimas Buddha" {{ $user->bidang === 'Bimas Buddha' ? 'selected' : '' }}>Bimas Buddha</option>
        </select>
    </div>

    <button type="submit" class="btn btn-success">
        <i class="bi bi-save"></i> Simpan Perubahan
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
