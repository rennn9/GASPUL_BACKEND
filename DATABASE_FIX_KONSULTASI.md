# Fix Database Konsultasi - Nama Tabel

## Masalah

Error: `SQLSTATE[42S02]: Base table or view not found: 1146 Table 'database.konsultasi' doesn't exist`

---

## Root Cause

Ada inkonsistensi antara:
1. **Migration file** membuat tabel `konsultasis` (plural - Laravel convention)
2. **Model** sekarang menggunakan `$table = 'konsultasi'` (singular)
3. **Production database** kemungkinan menggunakan `konsultasi` (singular)

---

## Solusi untuk Production (cPanel)

### Opsi 1: Jika Tabel Bernama `konsultasis` (Plural)

Rename tabel melalui phpMyAdmin atau MySQL:

```sql
-- Login ke MySQL via phpMyAdmin atau terminal
USE nama_database;

-- Rename tabel
RENAME TABLE konsultasis TO konsultasi;

-- Verify
SHOW TABLES LIKE 'konsultasi';
```

### Opsi 2: Jika Tabel Belum Ada

Buat tabel baru via phpMyAdmin atau jalankan query ini:

```sql
CREATE TABLE `konsultasi` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama_lengkap` varchar(255) NOT NULL,
  `no_hp` varchar(20) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `perihal` varchar(255) NOT NULL,
  `isi_konsultasi` text NOT NULL,
  `dokumen` varchar(255) DEFAULT NULL,
  `status` enum('baru','proses','selesai','batal') NOT NULL DEFAULT 'baru',
  `tanggal_konsultasi` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Solusi untuk Development (Local)

### Jika Tabel `konsultasis` Sudah Ada:

```bash
# Via Laravel Tinker
php artisan tinker
> DB::statement('RENAME TABLE konsultasis TO konsultasi');
> exit

# Atau via MySQL
mysql -u root -p
> USE gaspul_backend;
> RENAME TABLE konsultasis TO konsultasi;
> exit
```

### Jika Tabel Belum Ada:

```bash
php artisan migrate
```

---

## Verifikasi Setelah Fix

### Via Laravel Tinker:
```bash
php artisan tinker
> \App\Models\Konsultasi::count()
# Expected: Menampilkan jumlah data (atau 0 jika belum ada data)
```

### Via MySQL:
```sql
-- Check tabel ada
SHOW TABLES LIKE 'konsultasi';

-- Check struktur
DESCRIBE konsultasi;

-- Check data
SELECT COUNT(*) FROM konsultasi;
```

### Via Browser:
```
Login ke admin → Klik "Layanan Konsultasi"
Expected: Halaman tampil tanpa error
```

---

## Penjelasan Teknis

### Kenapa Terjadi Inkonsistensi?

**Migration File** (2025_10_20_075014_create_konsultasis_table.php):
```php
public function up(): void
{
    Schema::create('konsultasi', function (Blueprint $table) {  // ✅ Singular
        // ...
    });
}

public function down(): void
{
    Schema::dropIfExists('konsultasis');  // ❌ Plural (typo)
}
```

Nama file: `create_konsultasis_table.php` (plural)
Nama tabel di `up()`: `konsultasi` (singular)
Nama tabel di `down()`: `konsultasis` (plural)

**Jika migration di-rollback dan di-migrate ulang**, akan membuat tabel `konsultasis` karena Laravel generate nama dari nama file.

### Fix Permanen di Migration

Untuk konsistensi, bisa update migration file (opsional):

```php
public function down(): void
{
    Schema::dropIfExists('konsultasi');  // ✅ Sesuai dengan up()
}
```

Tapi karena migration sudah run di production, **lebih baik biarkan** dan gunakan `$table` property di model.

---

## Checklist Fix Production

- [ ] Login ke cPanel
- [ ] Buka phpMyAdmin
- [ ] Pilih database
- [ ] Check apakah tabel `konsultasis` ada
- [ ] Jika ya, rename ke `konsultasi`
- [ ] Jika tidak, buat tabel baru dengan query SQL
- [ ] Verify dengan query: `SELECT * FROM konsultasi LIMIT 1;`
- [ ] Test dari browser: login admin → klik menu Konsultasi
- [ ] Expected: Halaman tampil tanpa error

---

## Troubleshooting

### Error "Table already exists"

**Penyebab**: Tabel `konsultasi` sudah ada

**Solusi**: Skip create, tabel sudah benar. Clear cache:
```bash
php artisan config:clear
php artisan cache:clear
```

### Error "Cannot drop table"

**Penyebab**: Ada foreign key constraint

**Solusi**:
```sql
SET FOREIGN_KEY_CHECKS = 0;
RENAME TABLE konsultasis TO konsultasi;
SET FOREIGN_KEY_CHECKS = 1;
```

### Data hilang setelah rename?

**Penyebab**: Rename table tidak menghapus data, data masih ada

**Verify**:
```sql
SELECT COUNT(*) FROM konsultasi;
SELECT * FROM konsultasi LIMIT 5;
```

---

## Summary

### Root Cause:
Inkonsistensi nama tabel antara migration file (plural) dan actual table (singular)

### Fix Model:
```php
protected $table = 'konsultasi';  // ✅ Explicitly define
```

### Fix Database:
```sql
RENAME TABLE konsultasis TO konsultasi;  -- Jika tabel plural
-- atau
CREATE TABLE konsultasi ...;  -- Jika tabel belum ada
```

### Result:
✅ Menu Layanan Konsultasi berfungsi normal
✅ Query ke database berhasil
✅ Semua fitur konsultasi berfungsi
