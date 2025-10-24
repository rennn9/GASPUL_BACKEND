# Instruksi Deployment - Fix Error Submit Antrian

## Masalah yang Diperbaiki
1. ✅ API routes tidak terdaftar
2. ✅ CSRF protection memblokir mobile app
3. ✅ Imagick extension tidak terinstall di server
4. ✅ Error handling untuk debugging

## File yang Diubah

### 1. bootstrap/app.php
- Menambahkan API routes registration
- Menambahkan CSRF exception untuk route `/api/*`

### 2. app/Http/Controllers/AntrianController.php
- Mengganti QR Code dari PNG (butuh Imagick) ke SVG
- Menambahkan auto-create folder `public/tiket`
- Menambahkan try-catch error handling
- Menambahkan logging untuk debugging

### 3. resources/views/admin/exports/tiket_pdf.blade.php
- Mengubah QR code image dari PNG ke SVG format

## Langkah Deployment ke cPanel

### 1. Upload File
Upload 3 file berikut ke server produksi:
```
bootstrap/app.php
app/Http/Controllers/AntrianController.php
resources/views/admin/exports/tiket_pdf.blade.php
```

### 2. Clear Cache Laravel
Jalankan command berikut di terminal cPanel atau File Manager Terminal:
```bash
cd ~/GASPUL_BACKEND
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear
```

### 3. Set Permissions
Pastikan folder memiliki permission yang benar:
```bash
chmod -R 755 public/tiket
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

### 4. Verifikasi Route API
Cek apakah route API sudah terdaftar:
```bash
php artisan route:list | grep antrian
```

Harusnya muncul:
```
POST   api/antrian/submit ................. AntrianController@submit
```

## Testing dari Aplikasi Android

### Endpoint
```
URL: https://yourdomain.com/api/antrian/submit
Method: POST
Content-Type: application/json
```

### Request Body Example
```json
{
  "nama": "John Doe",
  "no_hp": "08123456789",
  "alamat": "Jl. Test No. 123",
  "bidang_layanan": "Pendidikan",
  "layanan": "Konsultasi",
  "tanggal_daftar": "2025-10-24",
  "keterangan": "Test antrian"
}
```

### Success Response
```json
{
  "success": true,
  "nomor_antrian": "001",
  "pdf_url": "https://yourdomain.com/tiket/2025-10-24-001.pdf",
  "qr_code_data": "base64_encoded_svg_string"
}
```

### Error Response (jika masih ada masalah)
```json
{
  "success": false,
  "message": "Terjadi kesalahan saat memproses data",
  "error": "Detail error message",
  "line": 64,
  "file": "/path/to/file.php"
}
```

## Troubleshooting

### Jika masih error 500:
1. Cek log Laravel di `storage/logs/laravel.log`
2. Error response sekarang menampilkan detail error, line, dan file
3. Pastikan database credentials di `.env` sudah benar

### Jika QR Code tidak muncul di PDF:
- SVG format sudah tidak memerlukan Imagick
- Jika tetap error, cek DomPDF version di `composer.json`

### Jika folder tiket error:
```bash
mkdir -p public/tiket
chmod 755 public/tiket
chown www-data:www-data public/tiket  # atau sesuai user web server
```

### Jika CSRF error masih muncul:
```bash
php artisan config:clear
php artisan route:clear
```

## Testing Manual via curl (opsional)

```bash
curl -X POST https://yourdomain.com/api/antrian/submit \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "nama": "Test User",
    "no_hp": "08123456789",
    "alamat": "Jl. Test",
    "bidang_layanan": "Pendidikan",
    "layanan": "Konsultasi",
    "tanggal_daftar": "2025-10-24",
    "keterangan": "Test"
  }'
```

## Checklist Deployment

- [ ] Upload 3 file yang diubah
- [ ] Clear semua cache Laravel
- [ ] Set permissions folder
- [ ] Verifikasi route API terdaftar
- [ ] Test submit dari aplikasi Android
- [ ] Cek data masuk ke database
- [ ] Cek PDF tiket ter-generate
- [ ] Verifikasi QR code tampil di PDF

## Contact
Jika masih ada masalah, share:
1. Error message dari response JSON
2. Screenshot error di aplikasi
3. Isi file `storage/logs/laravel.log` (baris terakhir)
