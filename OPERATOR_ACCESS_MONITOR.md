# Akses Operator ke Monitor Antrian

## Perubahan yang Dilakukan

Menambahkan akses role **Operator** untuk dapat mengakses halaman **Monitor Antrian**.

---

## File yang Diubah

### 1. routes/web.php
**Lokasi**: Line 62-68

**Perubahan**: Menambahkan middleware `role` untuk membatasi akses Monitor Antrian hanya untuk Superadmin, Admin, dan Operator.

```php
// -----------------------
// Monitor Antrian (Accessible by Superadmin, Admin, and Operator)
// -----------------------
Route::middleware('role:superadmin,admin,operator')->group(function () {
    Route::get('/monitor', [AntrianController::class, 'monitor'])->name('monitor');
    Route::get('/monitor/data', [AntrianController::class, 'monitorData'])->name('monitor.data');
});
```

**Sebelumnya**: Tanpa middleware khusus (semua user yang login bisa akses)
**Sekarang**: Hanya Superadmin, Admin, dan Operator yang bisa akses

---

### 2. resources/views/admin/layout.blade.php
**Lokasi**: Line 174-181 dan 207-225

#### Perubahan A: Badge Color untuk Operator
```php
<span class="badge
    @if(Auth::user()->role === 'superadmin') bg-danger
    @elseif(Auth::user()->role === 'admin') bg-success
    @elseif(Auth::user()->role === 'operator') bg-info
    @else bg-secondary @endif
">
    {{ ucfirst(Auth::user()->role) }}
</span>
```

**Badge Colors**:
- 🔴 **Superadmin** → Red (bg-danger)
- 🟢 **Admin** → Green (bg-success)
- 🔵 **Operator** → Blue (bg-info)

#### Perubahan B: Menu Navigation
**Sebelumnya**: Menu Monitor Antrian hanya tampil untuk Superadmin

**Sekarang**: Menu Monitor Antrian tampil untuk Superadmin, Admin, dan Operator

```php
{{-- Monitor Antrian: Accessible by Superadmin, Admin, and Operator --}}
@if(in_array(Auth::user()->role, ['superadmin', 'admin', 'operator']))
<li class="nav-item mb-2">
    <a class="nav-link {{ request()->is('admin/monitor*') ? 'active' : '' }}"
    href="{{ route('admin.monitor') }}">
        <i class="bi bi-display me-2"></i> <span>Monitor Antrian</span>
    </a>
</li>
@endif

{{-- User Management: Only Superadmin --}}
@if(Auth::user()->role === 'superadmin')
<li class="nav-item mb-2">
    <a class="nav-link {{ request()->is('admin/users*') ? 'active' : '' }}"
       href="{{ route('admin.users.index') }}">
        <i class="bi bi-people me-2"></i> <span>User Management</span>
    </a>
</li>
@endif
```

---

## Akses Menu Berdasarkan Role

| Menu | Superadmin | Admin | Operator |
|------|-----------|-------|----------|
| Statistik | ✅ | ✅ | ✅ |
| Daftar Antrian | ✅ | ✅ | ✅ |
| Layanan Konsultasi | ✅ | ✅ | ✅ |
| **Monitor Antrian** | ✅ | ✅ | ✅ |
| User Management | ✅ | ❌ | ❌ |

---

## Testing

### 1. Test Login sebagai Operator
```
Email: operator@example.com
Password: (sesuai database)
```

### 2. Verifikasi Menu Tampil
Setelah login sebagai Operator, pastikan:
- ✅ Menu "Monitor Antrian" tampil di sidebar
- ✅ Badge role tampil warna biru (bg-info)
- ❌ Menu "User Management" tidak tampil

### 3. Test Akses Route
Akses URL langsung:
```
https://yourdomain.com/admin/monitor
```

**Expected**: Halaman monitor antrian tampil dengan data real-time

### 4. Test Akses Ditolak untuk Route Lain
Coba akses:
```
https://yourdomain.com/admin/users
```

**Expected**: Error 403 (Forbidden) - Operator tidak memiliki akses ke User Management

---

## Cara Deploy ke Production

### Via Terminal cPanel:
```bash
cd ~/GASPUL_BACKEND

# Pull update dari GitHub
git pull origin main

# Clear cache
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear

# Verifikasi
php artisan route:list | grep monitor
```

Expected output:
```
GET|HEAD  admin/monitor .................. admin.monitor › AntrianController@monitor
GET|HEAD  admin/monitor/data ............ admin.monitor.data › AntrianController@monitorData
```

---

## Verifikasi Route Middleware

Check route list untuk memastikan middleware terpasang:

```bash
php artisan route:list --name=monitor
```

Output:
```
  GET|HEAD  admin/monitor ........................ admin.monitor › AntrianController@monitor
  GET|HEAD  admin/monitor/data ................... admin.monitor.data › AntrianController@monitorData
```

Middleware group: `web, auth, role:superadmin,admin,operator`

---

## Rollback (Jika Diperlukan)

Jika ada masalah, kembalikan ke commit sebelumnya:

```bash
git log --oneline -3
git reset --hard <commit-hash-sebelumnya>
php artisan config:clear
php artisan cache:clear
```

---

## Troubleshooting

### Menu tidak tampil untuk Operator
**Penyebab**: Cache view belum di-clear

**Solusi**:
```bash
php artisan view:clear
php artisan config:clear
```

### Error 403 saat akses Monitor
**Penyebab**: Role di database tidak sesuai atau session belum update

**Solusi**:
1. Check database: `SELECT id, name, email, role FROM users WHERE role = 'operator';`
2. Logout dan login ulang
3. Clear session: `php artisan cache:clear`

### Badge color tidak berubah
**Penyebab**: Browser cache

**Solusi**: Hard refresh browser (Ctrl+Shift+R atau Ctrl+F5)

---

## Struktur Role di Database

Table: `users`

| Column | Type | Values |
|--------|------|--------|
| id | int | Auto increment |
| name | varchar | Nama user |
| email | varchar | Email login |
| role | enum | 'superadmin', 'admin', 'operator' |

**Cara membuat user operator**:
```sql
INSERT INTO users (name, email, password, role, created_at, updated_at)
VALUES ('Operator 1', 'operator@gaspul.co', '$2y$12$...', 'operator', NOW(), NOW());
```

Atau via User Management (jika login sebagai Superadmin):
1. Login sebagai Superadmin
2. Menu "User Management"
3. Klik "Tambah User"
4. Pilih Role: Operator

---

## Checklist Deployment

- [ ] Update code di local: `git pull origin main`
- [ ] Clear cache: `php artisan config:clear`
- [ ] Clear route: `php artisan route:clear`
- [ ] Clear view: `php artisan view:clear`
- [ ] Verifikasi route: `php artisan route:list | grep monitor`
- [ ] Test login sebagai Operator
- [ ] Verifikasi menu Monitor tampil
- [ ] Verifikasi badge warna biru
- [ ] Test akses halaman monitor
- [ ] Verifikasi data real-time tampil
- [ ] Test User Management tidak bisa diakses Operator

---

## Summary

✅ **Role Operator sekarang dapat mengakses**:
- Statistik Pelayanan
- Daftar Antrian
- Layanan Konsultasi
- **Monitor Antrian** (NEW)

❌ **Role Operator TIDAK dapat mengakses**:
- User Management (hanya Superadmin)

🔵 **Badge Operator**: Warna biru (bg-info)
