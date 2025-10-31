# Fix: Monitor Antrian Redirect ke Login

## Masalah yang Terjadi

Ketika mengklik menu **Monitor Antrian**, user di-redirect kembali ke halaman login meskipun sudah login.

---

## Akar Masalah

### 1. Middleware `CheckRole` yang Salah

**File**: `app/Http/Middleware/CheckRole.php`

**Kode Lama (Bermasalah)**:
```php
public function handle($request, Closure $next, ...$roles)
{
    $userRole = $request->session()->get('user_role');  // ❌ MASALAH DI SINI

    if (! $userRole) {
        return redirect()->route('login');  // Redirect ke login
    }

    // ...
}
```

**Penyebab**:
- Middleware mencari data `user_role` di session
- Data `user_role` TIDAK PERNAH di-set ke session saat login
- `AuthController` hanya menggunakan `Auth::login($user)` tanpa set session manual
- Karena `$userRole` null, langsung redirect ke login

### 2. Alur Login yang Benar (Laravel Standard)

**File**: `app/Http/Controllers/AuthController.php` (Line 34-40)

```php
// Gunakan Auth bawaan Laravel
Auth::login($user);

// regenerate session
$request->session()->regenerate();

return redirect()->route('admin.dashboard');
```

Laravel menyimpan user authentication melalui `Auth::login()`, BUKAN melalui session manual.

---

## Solusi yang Diterapkan

### Update Middleware CheckRole

**File**: `app/Http/Middleware/CheckRole.php`

**Perubahan**:

#### Before:
```php
public function handle($request, Closure $next, ...$roles)
{
    $userRole = $request->session()->get('user_role');  // ❌ Salah

    if (! $userRole) {
        return redirect()->route('login');
    }

    // ...
}
```

#### After:
```php
use Illuminate\Support\Facades\Auth;

public function handle($request, Closure $next, ...$roles)
{
    // Check if user is authenticated
    if (! Auth::check()) {  // ✅ Menggunakan Auth facade
        return redirect()->route('login');
    }

    // Get user role from authenticated user
    $userRole = Auth::user()->role;  // ✅ Ambil dari Auth::user()

    // jika roles kosong, biarkan
    if (empty($roles)) {
        return $next($request);
    }

    if (! in_array($userRole, $roles)) {
        abort(403, 'Akses ditolak untuk role ' . $userRole);
    }

    return $next($request);
}
```

**Penjelasan**:
1. ✅ Menggunakan `Auth::check()` untuk cek apakah user sudah login
2. ✅ Menggunakan `Auth::user()->role` untuk ambil role dari database
3. ✅ Sesuai dengan Laravel authentication standard
4. ✅ Tidak perlu set session manual

---

## Alur Authentication yang Benar

### 1. Login Flow
```
User Input (NIP + Password)
    ↓
AuthController::login()
    ↓
Auth::login($user)  ← Menyimpan user ke Auth guard
    ↓
Session regenerate
    ↓
Redirect to dashboard
```

### 2. Authorization Flow (Route dengan middleware role)
```
Request ke /admin/monitor
    ↓
Middleware: auth (check Auth::check())
    ↓
Middleware: role:superadmin,admin,operator
    ↓
CheckRole::handle()
    ↓
Auth::check() → TRUE (user sudah login)
    ↓
Auth::user()->role → 'operator'
    ↓
in_array('operator', ['superadmin', 'admin', 'operator']) → TRUE
    ↓
Request diteruskan ke AntrianController@monitor
    ↓
View monitor.blade.php ditampilkan
```

---

## Testing

### 1. Test Login dan Akses Monitor

```bash
# Login ke aplikasi
URL: https://gaspul.co/login
NIP: [nip operator]
Password: [password]

# Setelah login, klik menu "Monitor Antrian"
# Expected: Halaman monitor tampil (tidak redirect ke login)
```

### 2. Test dengan Role Berbeda

#### Test sebagai Operator:
- ✅ Login berhasil
- ✅ Menu Monitor Antrian tampil
- ✅ Klik Monitor → Halaman monitor tampil
- ✅ Data real-time tampil

#### Test sebagai Admin:
- ✅ Login berhasil
- ✅ Menu Monitor Antrian tampil
- ✅ Klik Monitor → Halaman monitor tampil
- ✅ Data real-time tampil

#### Test sebagai Superadmin:
- ✅ Login berhasil
- ✅ Menu Monitor Antrian tampil
- ✅ Klik Monitor → Halaman monitor tampil
- ✅ Menu User Management tampil

### 3. Test Route Protection

#### Test sebagai Guest (belum login):
```
Akses: https://gaspul.co/admin/monitor
Expected: Redirect ke /login
```

#### Test sebagai role yang tidak punya akses:
Jika ada role lain selain superadmin, admin, operator:
```
Akses: https://gaspul.co/admin/monitor
Expected: Error 403 (Forbidden)
```

---

## Deploy ke Production

### Via Terminal cPanel:

```bash
cd ~/GASPUL_BACKEND

# Pull update
git pull origin main

# Clear semua cache
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear

# Test route
php artisan route:list | grep monitor
```

Expected output:
```
GET|HEAD  admin/monitor .............. admin.monitor › AntrianController@monitor
GET|HEAD  admin/monitor/data ......... admin.monitor.data › AntrianController@monitorData
```

### Verifikasi Middleware:

```bash
php artisan route:list --name=monitor --columns=uri,name,middleware
```

Expected:
```
URI                    | Name              | Middleware
-----------------------|-------------------|----------------------------------
admin/monitor          | admin.monitor     | web, auth, role:superadmin,admin,operator
admin/monitor/data     | admin.monitor.data| web, auth, role:superadmin,admin,operator
```

---

## File yang Diubah

1. ✅ `app/Http/Middleware/CheckRole.php`
   - Tambah `use Illuminate\Support\Facades\Auth`
   - Ganti `$request->session()->get('user_role')` dengan `Auth::user()->role`
   - Tambah `Auth::check()` untuk validasi authentication

2. ✅ `routes/web.php` (dari commit sebelumnya)
   - Tambah middleware `role:superadmin,admin,operator` untuk route monitor

3. ✅ `resources/views/admin/layout.blade.php` (dari commit sebelumnya)
   - Update menu visibility untuk operator

---

## Troubleshooting

### Masih redirect ke login setelah fix?

**Kemungkinan Penyebab**:
1. Cache belum di-clear
2. Session lama masih aktif

**Solusi**:
```bash
# Di server
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan session:clear

# Di browser
- Logout
- Clear browser cache (Ctrl+Shift+Delete)
- Login ulang
```

### Error "Auth::user() is null"?

**Kemungkinan Penyebab**: User belum login atau session expired

**Solusi**:
1. Logout dan login ulang
2. Check session lifetime di `.env`:
   ```
   SESSION_LIFETIME=120
   SESSION_DRIVER=file
   ```

### Error 403 meskipun sudah login?

**Kemungkinan Penyebab**: Role di database tidak sesuai

**Solusi**:
```sql
-- Check role user di database
SELECT id, name, nip, email, role FROM users;

-- Update role jika perlu
UPDATE users SET role = 'operator' WHERE nip = 'NIP_USER';
```

### Menu Monitor tidak tampil?

**Kemungkinan Penyebab**: View cache belum di-clear

**Solusi**:
```bash
php artisan view:clear
# Hard refresh browser (Ctrl+Shift+R)
```

---

## Penjelasan Teknis

### Kenapa Menggunakan Auth::user() daripada Session?

#### Laravel Standard Way (✅ Recommended):
```php
// Menggunakan Auth facade
if (Auth::check()) {
    $user = Auth::user();
    $role = $user->role;
}
```

**Kelebihan**:
- ✅ Built-in Laravel, fully supported
- ✅ Auto handle session dan cookie
- ✅ Support multiple guards (web, api, etc)
- ✅ Terintegrasi dengan middleware auth
- ✅ Easy testing

#### Manual Session Way (❌ Not Recommended):
```php
// Manual set session
$request->session()->put('user_role', $user->role);
$role = $request->session()->get('user_role');
```

**Kekurangan**:
- ❌ Harus manual set setiap login
- ❌ Harus manual hapus setiap logout
- ❌ Tidak konsisten dengan Laravel standard
- ❌ Sulit di-testing

---

## Checklist Deployment

- [ ] Pull update: `git pull origin main`
- [ ] Clear config: `php artisan config:clear`
- [ ] Clear route: `php artisan route:clear`
- [ ] Clear cache: `php artisan cache:clear`
- [ ] Clear view: `php artisan view:clear`
- [ ] Verifikasi middleware: `php artisan route:list | grep monitor`
- [ ] Logout dari admin
- [ ] Login ulang sebagai operator
- [ ] Klik menu "Monitor Antrian"
- [ ] Verifikasi halaman monitor tampil (tidak redirect)
- [ ] Verifikasi data real-time tampil
- [ ] Test logout
- [ ] Test login sebagai admin
- [ ] Test login sebagai superadmin

---

## Summary

### Root Cause:
❌ Middleware `CheckRole` menggunakan `$request->session()->get('user_role')` yang tidak pernah di-set

### Solution:
✅ Update middleware untuk menggunakan `Auth::check()` dan `Auth::user()->role`

### Result:
✅ Menu Monitor Antrian sekarang bisa diakses tanpa redirect ke login
✅ Operator, Admin, dan Superadmin bisa akses Monitor Antrian
✅ Sesuai dengan Laravel authentication standard
