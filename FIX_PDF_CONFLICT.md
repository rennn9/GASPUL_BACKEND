# Fix Conflict File PDF Tiket

## Masalah

```
error: The following untracked working tree files would be overwritten by merge:
        public/tiket/2025-10-24-002.pdf
        public/tiket/2025-10-24-003.pdf
        ...
        public/tiket/2025-10-31-001.pdf
Please move or remove them before you merge.
```

**Penyebab:** File PDF tiket yang ada di server bentrok dengan file yang akan di-download dari GitHub.

---

## ✅ Solusi: Backup lalu Pull

### Opsi 1: Backup File PDF (Aman)

```bash
cd ~/GASPUL_BACKEND

# Buat folder backup
mkdir -p ~/backup_tiket_$(date +%Y%m%d)

# Pindahkan file PDF yang bentrok ke backup
mv public/tiket/2025-10-24-002.pdf ~/backup_tiket_$(date +%Y%m%d)/ 2>/dev/null
mv public/tiket/2025-10-24-003.pdf ~/backup_tiket_$(date +%Y%m%d)/ 2>/dev/null
mv public/tiket/2025-10-24-004.pdf ~/backup_tiket_$(date +%Y%m%d)/ 2>/dev/null
mv public/tiket/2025-10-28-001.pdf ~/backup_tiket_$(date +%Y%m%d)/ 2>/dev/null
mv public/tiket/2025-10-29-*.pdf ~/backup_tiket_$(date +%Y%m%d)/ 2>/dev/null
mv public/tiket/2025-10-30-001.pdf ~/backup_tiket_$(date +%Y%m%d)/ 2>/dev/null
mv public/tiket/2025-10-31-001.pdf ~/backup_tiket_$(date +%Y%m%d)/ 2>/dev/null

# Sekarang pull dari GitHub
git pull origin main

# Clear cache
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear

# Verifikasi
git log --oneline -3
```

---

## 🚀 Opsi 2: Hapus File PDF (Lebih Cepat)

**Catatan:** File PDF ini akan didownload ulang dari GitHub, jadi aman untuk dihapus.

```bash
cd ~/GASPUL_BACKEND

# Hapus file PDF yang bentrok
rm -f public/tiket/2025-10-24-002.pdf
rm -f public/tiket/2025-10-24-003.pdf
rm -f public/tiket/2025-10-24-004.pdf
rm -f public/tiket/2025-10-28-001.pdf
rm -f public/tiket/2025-10-29-*.pdf
rm -f public/tiket/2025-10-30-001.pdf
rm -f public/tiket/2025-10-31-001.pdf

# Pull dari GitHub
git pull origin main

# Clear cache
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear

# Verifikasi
git log --oneline -3
```

---

## 💪 Opsi 3: Force dengan git clean (Paling Mudah)

```bash
cd ~/GASPUL_BACKEND

# Lihat file yang akan dihapus (preview)
git clean -n

# Hapus semua untracked files (termasuk PDF)
git clean -fd

# Pull dari GitHub
git pull origin main

# Clear cache
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear

# Set permissions
chmod -R 755 public/tiket storage bootstrap/cache

# Verifikasi
git log --oneline -3
```

Expected output setelah `git log`:
```
96dd8a1 (HEAD -> main, origin/main) Update dengan monitor
f6cc401 Perbaikan error submit antrian dari aplikasi mobile
548dc1b Menambahkan Management User untuk SuperAdmin
```

---

## 🎯 Command Lengkap (REKOMENDASI)

**Copy-paste command ini di terminal cPanel:**

```bash
cd ~/GASPUL_BACKEND && \
git clean -fd && \
git pull origin main && \
php artisan config:clear && \
php artisan route:clear && \
php artisan cache:clear && \
php artisan view:clear && \
chmod -R 755 public/tiket storage bootstrap/cache && \
echo "=== Deployment Success ===" && \
git log --oneline -3
```

Output yang diharapkan:
```
Removing public/tiket/2025-10-24-002.pdf
Removing public/tiket/2025-10-24-003.pdf
...
Updating 548dc1b..96dd8a1
Fast-forward
...
Configuration cache cleared successfully.
Route cache cleared successfully.
Application cache cleared successfully.
Compiled views cleared successfully.
=== Deployment Success ===
96dd8a1 (HEAD -> main, origin/main) ...
f6cc401 Perbaikan error submit antrian...
548dc1b Menambahkan Management User...
```

---

## 🧪 Testing Setelah Berhasil

### 1. Verifikasi commit terbaru
```bash
git log --oneline -1
```
Harusnya: `96dd8a1`

### 2. Cek route monitor (fitur baru)
```bash
php artisan route:list | grep monitor
```
Harusnya muncul route:
```
GET|HEAD  admin/monitor ........................ monitor
GET|HEAD  admin/monitor/data ................... monitor.data
```

### 3. Test API submit antrian
```bash
curl -X POST https://gaspul.co/api/antrian/submit \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "nama": "Test After Deploy",
    "no_hp": "08123456789",
    "alamat": "Jl. Test Deployment",
    "bidang_layanan": "Pendidikan",
    "layanan": "Konsultasi",
    "tanggal_daftar": "2025-10-31",
    "keterangan": "Testing deployment"
  }'
```

Response yang diharapkan:
```json
{
  "success": true,
  "nomor_antrian": "002",
  "pdf_url": "https://gaspul.co/tiket/2025-10-31-002.pdf",
  "qr_code_data": "..."
}
```

### 4. Test dari browser
- Login admin: `https://gaspul.co/admin/login`
- Dashboard: `https://gaspul.co/admin/dashboard`
- **Monitor antrian** (baru): `https://gaspul.co/admin/monitor`
- Daftar antrian: `https://gaspul.co/admin/antrian`

### 5. Test dari aplikasi Android
- Buka aplikasi GASPUL
- Isi form antrian
- Submit
- Verifikasi:
  - Data masuk ke database
  - Muncul di admin dashboard
  - PDF tiket ter-generate
  - QR code bisa discan

---

## ❓ FAQ

### Q: File PDF akan hilang?
**A:** Tidak. File PDF yang sama akan didownload dari GitHub. File PDF adalah hasil testing yang sudah di-commit ke repo.

### Q: Apakah data database akan hilang?
**A:** Tidak. Command ini hanya update code, tidak menyentuh database.

### Q: Bagaimana kalau ada error setelah update?
**A:** Bisa rollback dengan:
```bash
git reset --hard 548dc1b
php artisan config:clear
php artisan cache:clear
```

### Q: File backup ada dimana?
**A:** Jika pakai Opsi 1, ada di `~/backup_tiket_YYYYMMDD/`

---

## ✅ Checklist

- [ ] cd ~/GASPUL_BACKEND
- [ ] git clean -fd (hapus untracked files)
- [ ] git pull origin main
- [ ] php artisan config:clear
- [ ] php artisan route:clear
- [ ] php artisan cache:clear
- [ ] php artisan view:clear
- [ ] chmod -R 755 folders
- [ ] git log -3 (verify commit 96dd8a1)
- [ ] Test API endpoint
- [ ] Test halaman monitor
- [ ] Test dari aplikasi Android

---

## 📞 Still Need Help?

Jika masih ada masalah, share:
1. Output dari: `git status`
2. Output dari: `ls -la public/tiket/ | head -20`
3. Output dari: `git log --oneline -3`
