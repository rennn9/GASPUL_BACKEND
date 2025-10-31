# Cara Resolve Git Conflict di cPanel

## Masalah yang Terjadi

```
error: Your local changes to the following files would be overwritten by merge:
        app/Http/Controllers/AntrianController.php
        bootstrap/app.php
        resources/views/admin/exports/tiket_pdf.blade.php
        routes/web.php
```

**Penyebab:** File-file di server production masih versi lama (sebelum fix SVG), sedangkan di GitHub sudah versi baru.

---

## ✅ Solusi: Stash Changes lalu Pull

Jalankan command ini di terminal cPanel **secara berurutan**:

### 1. Backup perubahan lokal (stash)
```bash
git stash
```
Output yang diharapkan:
```
Saved working directory and index state WIP on main: ...
```

### 2. Pull update dari GitHub
```bash
git pull origin main
```
Output yang diharapkan:
```
Updating 548dc1b..96dd8a1
Fast-forward
...
```

### 3. Clear cache Laravel
```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear
```

### 4. Set permissions
```bash
chmod -R 755 public/tiket
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

### 5. Verifikasi update berhasil
```bash
git log --oneline -3
```

Harusnya muncul commit terbaru:
```
96dd8a1 (HEAD -> main, origin/main) ...
f6cc401 Perbaikan error submit antrian dari aplikasi mobile
548dc1b Menambahkan Management User untuk SuperAdmin
```

---

## 🔄 Alternatif: Force Update (Buang Perubahan Lokal)

**⚠️ PERHATIAN:** Ini akan menghapus semua perubahan lokal di server!

Jika Anda yakin tidak ada perubahan penting di server production:

```bash
# Reset ke state GitHub (hapus perubahan lokal)
git reset --hard origin/main

# Bersihkan untracked files
git clean -fd

# Verifikasi
git status
```

Output yang diharapkan:
```
On branch main
Your branch is up to date with 'origin/main'.
nothing to commit, working tree clean
```

Lalu clear cache:
```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear
```

---

## 📝 Command Lengkap (Copy-Paste Semua)

### Opsi 1: Dengan Stash (Aman)
```bash
cd ~/GASPUL_BACKEND
git stash
git pull origin main
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear
chmod -R 755 public/tiket storage bootstrap/cache
git log --oneline -3
```

### Opsi 2: Force Update (Hapus Perubahan Lokal)
```bash
cd ~/GASPUL_BACKEND
git reset --hard origin/main
git clean -fd
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear
chmod -R 755 public/tiket storage bootstrap/cache
git log --oneline -3
```

---

## 🧪 Testing Setelah Update

### 1. Cek versi commit
```bash
git log --oneline -1
```
Harusnya: `96dd8a1`

### 2. Cek route terdaftar
```bash
php artisan route:list | grep monitor
```
Harusnya muncul route monitor (fitur baru)

### 3. Test API dari terminal
```bash
curl -X POST https://gaspul.co/api/antrian/submit \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "nama": "Test Deploy",
    "no_hp": "08123456789",
    "alamat": "Jl. Test",
    "bidang_layanan": "Pendidikan",
    "layanan": "Konsultasi",
    "tanggal_daftar": "2025-10-31",
    "keterangan": "Test after update"
  }'
```

Expected response:
```json
{
  "success": true,
  "nomor_antrian": "002",
  "pdf_url": "https://gaspul.co/tiket/2025-10-31-002.pdf",
  "qr_code_data": "base64_string..."
}
```

### 4. Test dari browser
- Login admin: `https://gaspul.co/admin/login`
- Cek halaman Monitor: `https://gaspul.co/admin/monitor` (fitur baru)
- Cek Daftar Antrian: `https://gaspul.co/admin/antrian`

### 5. Test dari aplikasi Android
- Submit form antrian
- Cek data masuk
- Download PDF tiket
- Scan QR code

---

## ❌ Troubleshooting

### Masih error setelah pull?

**Cek log error:**
```bash
tail -50 storage/logs/laravel.log
```

**Cek status Git:**
```bash
git status
```

**Cek permission:**
```bash
ls -la storage/
ls -la bootstrap/cache/
ls -la public/tiket/
```

### Error "Permission denied"

```bash
chmod -R 755 storage
chmod -R 755 bootstrap/cache
chmod -R 755 public/tiket
chown -R $USER:$USER storage
chown -R $USER:$USER bootstrap/cache
```

### Route tidak ditemukan

```bash
php artisan route:clear
php artisan config:clear
php artisan cache:clear
composer dump-autoload
```

### Database error

Cek file `.env`:
```bash
cat .env | grep DB_
```

Pastikan kredensial database benar.

---

## 🔙 Rollback Jika Ada Masalah

Jika setelah update ada error dan perlu rollback:

```bash
# Lihat commit sebelumnya
git log --oneline -5

# Rollback ke commit sebelumnya (548dc1b - versi sebelum fix)
git reset --hard 548dc1b

# Clear cache
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

**CATATAN:** Ini akan mengembalikan ke versi sebelum fix error Imagick, jadi aplikasi mobile akan error lagi saat submit.

---

## ✅ Checklist Deployment

- [ ] cd ~/GASPUL_BACKEND
- [ ] git stash (atau git reset --hard origin/main)
- [ ] git pull origin main
- [ ] php artisan config:clear
- [ ] php artisan route:clear
- [ ] php artisan cache:clear
- [ ] php artisan view:clear
- [ ] chmod -R 755 public/tiket storage bootstrap/cache
- [ ] git log --oneline -3 (verify commit)
- [ ] Test API endpoint
- [ ] Test admin dashboard
- [ ] Test halaman monitor (baru)
- [ ] Test dari aplikasi Android

---

## 📞 Jika Masih Ada Masalah

Share informasi berikut:
1. Output dari: `git status`
2. Output dari: `git log --oneline -3`
3. Output dari: `tail -20 storage/logs/laravel.log`
4. Screenshot error dari browser/aplikasi
