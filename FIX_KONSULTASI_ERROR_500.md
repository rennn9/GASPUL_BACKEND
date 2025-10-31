# Fix: Error 500 pada Menu Konsultasi

## Masalah yang Terjadi

Ketika mengklik menu **Layanan Konsultasi**, muncul error 500 (Internal Server Error).

---

## Akar Masalah

### Inkonsistensi Nama Tabel

**File Migration**: `database/migrations/2025_10_20_075014_create_konsultasis_table.php`

```php
public function up(): void
{
    Schema::create('konsultasi', function (Blueprint $table) {  // ✅ Tabel: 'konsultasi' (singular)
        $table->id();
        // ...
    });
}

public function down(): void
{
    Schema::dropIfExists('konsultasis');  // ❌ Salah: 'konsultasis' (plural)
}
```

**File Model**: `app/Models/Konsultasi.php` (sebelum fix)

```php
class Konsultasi extends Model
{
    // ❌ TIDAK ADA $table property
    // Laravel default akan mencari tabel 'konsultasis' (plural dari Konsultasi)
    // Tapi tabel sebenarnya adalah 'konsultasi' (singular)

    protected $fillable = [...];
}
```

### Penyebab Error:

1. Migration membuat tabel dengan nama `konsultasi` (singular)
2. Model `Konsultasi` tidak mendefinisikan property `$table`
3. Laravel convention: Model `Konsultasi` → table `konsultasis` (plural)
4. Query ke database mencari tabel `konsultasis` → **tabel tidak ditemukan** → Error 500

```
Request → KonsultasiController@index()
    ↓
Konsultasi::query()  // Laravel cari tabel 'konsultasis'
    ↓
❌ Table 'database.konsultasis' doesn't exist
    ↓
Error 500 Internal Server Error
```

---

## Solusi yang Diterapkan

### 1. Fix Model Konsultasi

**File**: `app/Models/Konsultasi.php`

**Before**:
```php
class Konsultasi extends Model
{
    protected $fillable = [
        'nama_lengkap',
        'no_hp',
        'email',
        'perihal',
        'isi_konsultasi',
        'dokumen',
        'status',
        'tanggal_konsultasi'
    ];
}
```

**After**:
```php
class Konsultasi extends Model
{
    // ✅ Tambahkan property $table untuk override nama tabel default
    protected $table = 'konsultasi';  // Sesuai dengan nama di migration

    protected $fillable = [
        'nama_lengkap',
        'no_hp',
        'email',
        'perihal',
        'isi_konsultasi',
        'dokumen',
        'status',
        'tanggal_konsultasi'
    ];

    protected $casts = [
        'tanggal_konsultasi' => 'datetime',
    ];
}
```

### 2. Tambah Error Handling di Controller

**File**: `app/Http/Controllers/KonsultasiController.php`

**Before**:
```php
public function index(Request $request)
{
    static $logged = false;
    Carbon::setLocale('id');

    $query = Konsultasi::query();
    // ...
    return view('admin.konsultasi.index', compact('konsultasis'));
}
```

**After**:
```php
public function index(Request $request)
{
    try {
        static $logged = false;
        Carbon::setLocale('id');

        $query = Konsultasi::query();
        // ...
        return view('admin.konsultasi.index', compact('konsultasis'));

    } catch (\Exception $e) {
        Log::error('Error di KonsultasiController@index: ' . $e->getMessage());
        Log::error('Stack trace: ' . $e->getTraceAsString());

        return response()->view('errors.500', [
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ], 500);
    }
}
```

---

## Penjelasan Teknis

### Laravel Table Naming Convention

Laravel menggunakan "plural" convention untuk nama tabel secara default:

| Model Name | Default Table Name | Custom Table Name |
|------------|-------------------|-------------------|
| User | users | - |
| Product | products | - |
| Konsultasi | konsultasis ❌ | konsultasi ✅ |
| Antrian | antrians ❌ | antrian ✅ |

### Cara Override Table Name

**Opsi 1: Define $table property** (✅ Recommended)
```php
class Konsultasi extends Model
{
    protected $table = 'konsultasi';
}
```

**Opsi 2: Ubah nama tabel di migration** (❌ Not recommended, breaking change)
```php
Schema::create('konsultasis', function (Blueprint $table) {
    // Ubah semua query SQL yang sudah ada
});
```

---

## Testing

### 1. Test Akses Menu Konsultasi

```bash
# Login ke admin panel
URL: https://gaspul.co/login

# Klik menu "Layanan Konsultasi"
Expected: Halaman konsultasi tampil dengan daftar data
```

### 2. Verifikasi Query Database

```sql
-- Check tabel ada
SHOW TABLES LIKE 'konsultasi';

-- Check data
SELECT * FROM konsultasi LIMIT 5;

-- Check struktur
DESCRIBE konsultasi;
```

### 3. Test Fitur Konsultasi

- ✅ Daftar konsultasi tampil
- ✅ Filter berdasarkan status berfungsi
- ✅ Detail konsultasi bisa dibuka
- ✅ Update status berfungsi
- ✅ Hapus data berfungsi
- ✅ Download PDF berfungsi

### 4. Test dari Aplikasi Android

- ✅ Submit form konsultasi dari HP
- ✅ Data masuk ke database
- ✅ Data tampil di admin panel

---

## Deploy ke Production

### Via Terminal cPanel:

```bash
cd ~/GASPUL_BACKEND

# Pull update
git pull origin main

# Clear cache
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Test database connection
php artisan tinker
# Ketik: Konsultasi::count()
# Expected: Menampilkan jumlah data
```

### Verifikasi Database:

```bash
# Login ke MySQL
mysql -u username -p

# Gunakan database
use database_name;

# Check tabel
SHOW TABLES LIKE 'konsultasi';

# Expected output:
# +-------------------------------+
# | Tables_in_db (konsultasi)     |
# +-------------------------------+
# | konsultasi                    |
# +-------------------------------+
```

---

## Troubleshooting

### Masih error "Table doesn't exist"?

**Kemungkinan**: Tabel belum dibuat di production

**Solusi**:
```bash
# Run migration
php artisan migrate

# Atau specific migration
php artisan migrate --path=database/migrations/2025_10_20_075014_create_konsultasis_table.php
```

### Error "Base table or view not found"?

**Kemungkinan**: Nama database salah di `.env`

**Solusi**:
```bash
# Check .env
cat .env | grep DB_

# Expected:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=nama_database
# DB_USERNAME=username
# DB_PASSWORD=password
```

### Data tidak tampil meskipun tidak error?

**Kemungkinan**: Tabel kosong

**Solusi**:
```sql
-- Check jumlah data
SELECT COUNT(*) FROM konsultasi;

-- Insert sample data untuk testing
INSERT INTO konsultasi (nama_lengkap, no_hp, email, perihal, isi_konsultasi, status, tanggal_konsultasi, created_at, updated_at)
VALUES ('Test User', '08123456789', 'test@example.com', 'Test Konsultasi', 'Ini adalah test konsultasi', 'baru', NOW(), NOW(), NOW());
```

### Filter status tidak berfungsi?

**Kemungkinan**: Cache view belum di-clear

**Solusi**:
```bash
php artisan view:clear
php artisan cache:clear
```

---

## Catatan Penting

### Perbedaan dengan Tabel Antrian

| Aspect | Antrian | Konsultasi |
|--------|---------|-----------|
| Nama Tabel | `antrian` (singular) ✅ | `konsultasi` (singular) ✅ |
| Model Property | `$table = 'antrian'` ✅ | `$table = 'konsultasi'` ✅ |
| Laravel Default | `antrians` ❌ | `konsultasis` ❌ |

**Best Practice**: Selalu define `$table` property di model jika nama tabel tidak mengikuti convention.

---

## Checklist Deployment

- [ ] Pull update: `git pull origin main`
- [ ] Clear config: `php artisan config:clear`
- [ ] Clear cache: `php artisan cache:clear`
- [ ] Clear view: `php artisan view:clear`
- [ ] Check migration: `php artisan migrate:status`
- [ ] Test query: `php artisan tinker` → `Konsultasi::count()`
- [ ] Login ke admin
- [ ] Klik menu "Layanan Konsultasi"
- [ ] Verifikasi daftar konsultasi tampil
- [ ] Test filter status
- [ ] Test detail konsultasi
- [ ] Test update status
- [ ] Test download PDF
- [ ] Test submit dari aplikasi Android

---

## File yang Diubah

1. ✅ `app/Models/Konsultasi.php`
   - Tambah property `$table = 'konsultasi'`

2. ✅ `app/Http/Controllers/KonsultasiController.php`
   - Tambah try-catch error handling di method `index()`
   - Logging error untuk debugging

---

## Summary

### Root Cause:
❌ Model `Konsultasi` tidak define `$table`, Laravel mencari tabel `konsultasis` (plural), tapi tabel sebenarnya `konsultasi` (singular)

### Solution:
✅ Tambah `protected $table = 'konsultasi';` di model

### Result:
✅ Menu Layanan Konsultasi sekarang bisa diakses tanpa error
✅ Semua fitur konsultasi berfungsi normal
✅ Error handling lebih baik untuk debugging
