# Panduan Deployment ke cPanel

## Cara 1: Menggunakan Terminal cPanel (Recommended)

### Langkah 1: Login ke cPanel
1. Login ke cPanel hosting Anda
2. Cari menu **Terminal** atau **SSH Access**
3. Klik untuk membuka terminal

### Langkah 2: Masuk ke Direktori Project
```bash
cd ~/GASPUL_BACKEND
# atau
cd ~/public_html/GASPUL_BACKEND
```

### Langkah 3: Pull Update dari GitHub
```bash
git pull origin main
```

### Langkah 4: Clear Cache Laravel
```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear
```

### Langkah 5: Set Permissions
```bash
chmod -R 755 public/tiket
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

### Langkah 6: Verifikasi
```bash
# Lihat commit terbaru
git log --oneline -3

# Cek route API
php artisan route:list | grep antrian
```

---

## Cara 2: Menggunakan Script Otomatis

### Upload script deploy-to-cpanel.sh
1. Upload file `deploy-to-cpanel.sh` ke root project di cPanel

### Jalankan script
```bash
cd ~/GASPUL_BACKEND
bash deploy-to-cpanel.sh
```

---

## Cara 3: Manual via File Manager cPanel

Jika tidak ada akses terminal:

### 1. Download File dari GitHub
Download file-file yang berubah dari GitHub:
- `app/Http/Controllers/AntrianController.php`
- `resources/views/admin/monitor.blade.php` (file baru)
- `resources/views/admin/layout.blade.php`
- `routes/web.php`

### 2. Upload via File Manager
1. Login ke cPanel
2. Buka **File Manager**
3. Navigate ke folder `GASPUL_BACKEND`
4. Upload file-file yang didownload ke lokasi yang sesuai
5. Overwrite file yang sudah ada

### 3. Clear Cache via PHP Script
Buat file `clear-cache.php` di root project:
```php
<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

// Clear config
\Illuminate\Support\Facades\Artisan::call('config:clear');
echo "Config cleared\n";

// Clear route
\Illuminate\Support\Facades\Artisan::call('route:clear');
echo "Route cleared\n";

// Clear cache
\Illuminate\Support\Facades\Artisan::call('cache:clear');
echo "Cache cleared\n";

// Clear view
\Illuminate\Support\Facades\Artisan::call('view:clear');
echo "View cleared\n";

echo "\nAll cache cleared successfully!";
?>
```

Akses file tersebut via browser:
```
https://yourdomain.com/clear-cache.php
```

Hapus file setelah selesai untuk keamanan.

---

## Perubahan pada Update Terbaru

### Fitur Baru:
1. **Monitor Antrian** - Halaman untuk monitoring antrian real-time
   - Route: `/admin/monitor`
   - View: `resources/views/admin/monitor.blade.php`

2. **API Monitor Data** - Endpoint untuk data monitor
   - Route: `/admin/monitor/data`

### File yang Berubah:
1. `app/Http/Controllers/AntrianController.php`
   - Tambah method `monitor()` dan `monitorData()`
   - Update sorting di method `table()`

2. `resources/views/admin/layout.blade.php`
   - Update navigasi menu

3. `routes/web.php`
   - Tambah route monitor

4. File PDF tiket baru (2025-10-24 s/d 2025-10-31)
   - Ukuran lebih kecil karena menggunakan SVG

---

## Testing Setelah Deployment

### 1. Test API Antrian
```bash
curl -X POST https://yourdomain.com/api/antrian/submit \
  -H "Content-Type: application/json" \
  -d '{
    "nama": "Test User",
    "no_hp": "08123456789",
    "alamat": "Jl. Test",
    "bidang_layanan": "Pendidikan",
    "layanan": "Konsultasi",
    "tanggal_daftar": "2025-10-31",
    "keterangan": "Test deployment"
  }'
```

### 2. Test Admin Dashboard
- Login ke admin: `https://yourdomain.com/admin/login`
- Cek halaman Daftar Antrian
- Cek halaman Monitor Antrian (fitur baru)

### 3. Test dari Aplikasi Android
- Submit form antrian
- Verifikasi data masuk
- Download PDF tiket

---

## Troubleshooting

### Error 500 Internal Server Error
```bash
# Cek log error
tail -f storage/logs/laravel.log

# Atau via cPanel Error Log
# cPanel > Metrics > Errors
```

### Route tidak ditemukan
```bash
# Clear route cache
php artisan route:clear
php artisan config:clear
```

### Permission denied
```bash
# Set permissions
chmod -R 755 storage
chmod -R 755 bootstrap/cache
chmod -R 755 public/tiket
```

### Git pull conflict
```bash
# Stash local changes
git stash

# Pull latest
git pull origin main

# Apply stash if needed
git stash pop
```

### Database connection error
```bash
# Cek file .env
# Pastikan DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD benar
```

---

## Checklist Deployment

- [ ] Login ke cPanel
- [ ] Buka Terminal atau SSH
- [ ] Navigate ke folder project
- [ ] Git pull origin main
- [ ] Clear semua cache Laravel
- [ ] Set folder permissions
- [ ] Verifikasi route terdaftar
- [ ] Test API endpoint
- [ ] Test admin dashboard
- [ ] Test halaman monitor (fitur baru)
- [ ] Test dari aplikasi mobile
- [ ] Verifikasi data masuk database
- [ ] Cek PDF tiket ter-generate

---

## Rollback (Jika Ada Masalah)

### Kembali ke commit sebelumnya
```bash
# Lihat commit history
git log --oneline

# Rollback ke commit sebelumnya (f6cc401)
git reset --hard f6cc401

# Force push (hati-hati!)
git push -f origin main

# Clear cache
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

---

## Kontak Support

Jika mengalami kesulitan:
1. Screenshot error message
2. Copy isi file `storage/logs/laravel.log` (baris terakhir)
3. Informasi versi PHP: `php -v`
4. Informasi Laravel: `php artisan --version`
