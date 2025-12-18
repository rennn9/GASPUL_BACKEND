# 🧪 React Testing Guide - Dynamic Survey System

**Date:** 2025-12-17
**Status:** Ready for testing

---

## 📋 Pre-Testing Checklist

### 1. Backend Prerequisites

Pastikan backend sudah running:

```bash
cd c:\Users\wijay\AppData\Local\GASPUL_BACKEND
php artisan serve
```

Akses di browser: http://192.168.1.5:8000 (atau http://localhost:8000)

### 2. Check Active Template

Pastikan ada template aktif:

```bash
php artisan tinker
```

```php
// Cek semua templates
App\Models\SurveyTemplate::all(['id', 'nama', 'is_active', 'versi']);

// Jika belum ada yang active, aktifkan template 11:
App\Models\SurveyTemplate::find(11)->update(['is_active' => true]);

// Verifikasi pertanyaan ada:
App\Models\SurveyTemplate::find(11)->questions()->count();
// Harus return > 0
```

### 3. Test API Endpoints Manually

#### Test GET Questions:
```bash
curl http://192.168.1.5:8000/api/survey/questions
```

Expected response:
```json
{
  "success": true,
  "data": {
    "template": {
      "id": 11,
      "nama": "...",
      "versi": 2
    },
    "questions": [...]
  }
}
```

#### Test CORS (jika perlu):
```bash
curl -H "Origin: http://localhost:3000" \
     -H "Access-Control-Request-Method: GET" \
     -H "Access-Control-Request-Headers: Content-Type" \
     -X OPTIONS \
     http://192.168.1.5:8000/api/survey/questions -v
```

Harus return `Access-Control-Allow-Origin: *` atau `http://localhost:3000`

---

## 🚀 Starting React App

### 1. Navigate to React Project

```bash
cd "c:\Users\wijay\AppData\Local\React Project\gaspul-web"
```

### 2. Install Dependencies (jika belum)

```bash
npm install
```

### 3. Start Development Server

```bash
npm run dev
# atau
npm start
```

Biasanya akan buka di:
- http://localhost:3000
- http://localhost:3001 (jika 3000 sudah dipakai)

---

## 🧪 Manual Testing Steps

### Test 1: Modal Opens Successfully

**Steps:**
1. Buka web app di browser
2. Navigate ke halaman yang memiliki survey modal (biasanya setelah submit layanan)
3. Klik button untuk buka modal survey

**Expected:**
- ✅ Modal terbuka dengan smooth animation
- ✅ Loading spinner muncul sebentar (~200-500ms)
- ✅ Survey questions muncul setelah loading selesai

**Troubleshooting:**
- Modal tidak muncul → Check console browser (F12)
- Loading forever → Check Network tab, apakah API call 200 OK?
- Error message → Baca error, cek di backend log

---

### Test 2: Survey Questions Display

**Steps:**
1. Setelah modal terbuka dan loading selesai
2. Perhatikan pertanyaan yang muncul

**Expected:**
- ✅ Pertanyaan muncul sesuai urutan (urutan 1, 2, 3, ...)
- ✅ Pertanyaan required ada tanda bintang merah (*)
- ✅ Pilihan jawaban muncul sesuai urutan
- ✅ Pilihan jawaban bisa diklik
- ✅ Visual feedback saat pilih jawaban (border biru, background biru muda)

**Check:**
- Jumlah pertanyaan sesuai dengan database?
  ```sql
  SELECT COUNT(*) FROM survey_questions WHERE survey_template_id = 11;
  ```
- Text pertanyaan benar?
- Options lengkap dan urutan benar?

---

### Test 3: Text Input Questions (jika ada)

**Steps:**
1. Jika ada pertanyaan dengan `is_text_input = true`, cari pertanyaan tersebut
2. Klik di textarea

**Expected:**
- ✅ Textarea muncul untuk pertanyaan text input
- ✅ Placeholder text muncul: "Masukkan jawaban Anda..."
- ✅ Bisa mengetik dengan lancar
- ✅ Jika required, tidak bisa submit jika kosong

**Create Test Question:**
```bash
php artisan tinker
```

```php
$template = App\Models\SurveyTemplate::find(11);
$template->questions()->create([
    'pertanyaan' => 'Apa saran Anda untuk perbaikan layanan?',
    'kode_unsur' => null,
    'urutan' => 999,
    'is_required' => false,
    'is_text_input' => true,
]);
```

Refresh modal, harus muncul textarea di akhir.

---

### Test 4: Required Field Validation

**Steps:**
1. Jangan isi semua pertanyaan
2. Klik "Kirim Survey"

**Expected:**
- ✅ Error message muncul: "Mohon jawab semua pertanyaan yang wajib diisi (X pertanyaan belum dijawab)."
- ✅ Error berwarna merah dengan icon warning

**Steps:**
1. Isi semua pertanyaan required
2. Jangan isi data diri (usia, jenis kelamin, dll)
3. Klik "Kirim Survey"

**Expected:**
- ✅ Error: "Mohon isi semua data diri (usia, jenis kelamin, pendidikan, pekerjaan)."

**Steps:**
1. Isi usia = 0 atau 150
2. Klik "Kirim Survey"

**Expected:**
- ✅ Error: "Usia harus antara 1-120 tahun."

---

### Test 5: Successful Submission

**Steps:**
1. Isi SEMUA pertanyaan required dengan benar
2. Isi data diri lengkap:
   - Usia: 25
   - Jenis Kelamin: Laki-laki
   - Pendidikan: S1
   - Pekerjaan: PNS
3. (Optional) Isi kritik/saran
4. Klik "Kirim Survey"

**Expected:**
- ✅ Button berubah jadi "Mengirim..." dan disabled
- ✅ Setelah ~500ms-2s, success message muncul
- ✅ Icon centang hijau besar muncul
- ✅ Text: "Survey Berhasil Dikirim!"
- ✅ Text: "Terima kasih atas partisipasi Anda..."
- ✅ Setelah 1.5 detik, modal auto-close
- ✅ Callback `onSuccess()` dipanggil

**Verify in Database:**
```sql
-- Cek survey terakhir
SELECT * FROM surveys ORDER BY id DESC LIMIT 1;

-- Harus ada survey_template_id = 11
-- nama_responden, usia, dll harus sesuai input

-- Cek responses
SELECT sr.*, sq.pertanyaan, sqo.jawaban, sqo.poin
FROM survey_responses sr
JOIN survey_questions sq ON sr.survey_question_id = sq.id
LEFT JOIN survey_question_options sqo ON sr.survey_question_option_id = sqo.id
WHERE sr.survey_id = [ID_DARI_QUERY_ATAS]
ORDER BY sq.urutan;

-- Jumlah responses harus sesuai jumlah pertanyaan yang dijawab
```

---

### Test 6: Duplicate Submission Prevention

**Steps:**
1. Submit survey untuk `layanan_publik_id = 123` (contoh)
2. Coba submit lagi untuk `layanan_publik_id = 123`

**Expected:**
- ✅ Error message: "Survey untuk layanan ini sudah pernah diisi."
- ✅ HTTP status 422

**Note:** Ini tergantung logika backend. Cek di `SurveyController.php` apakah ada validasi duplicate.

---

### Test 7: Error Handling - No Active Template

**Steps:**
1. Nonaktifkan semua template:
   ```bash
   php artisan tinker
   App\Models\SurveyTemplate::query()->update(['is_active' => false]);
   ```
2. Buka modal survey
3. Tunggu loading selesai

**Expected:**
- ✅ Error message: "Tidak ada template survey aktif saat ini."
- ✅ Icon warning merah
- ✅ Button "Coba Lagi" muncul
- ✅ Klik "Coba Lagi" → retry fetch

**Cleanup:**
```bash
php artisan tinker
App\Models\SurveyTemplate::find(11)->update(['is_active' => true]);
```

---

### Test 8: Error Handling - Network Error

**Steps:**
1. Stop backend server: `Ctrl+C` di terminal backend
2. Buka modal survey
3. Tunggu loading

**Expected:**
- ✅ Error message: "Gagal memuat pertanyaan survey. Silakan coba lagi."
- ✅ Button "Coba Lagi" muncul

**Steps:**
4. Start backend lagi: `php artisan serve`
5. Klik "Coba Lagi"

**Expected:**
- ✅ Loading muncul
- ✅ Survey questions berhasil dimuat

---

### Test 9: Modal Close & Reset

**Steps:**
1. Buka modal
2. Isi beberapa jawaban (tapi jangan submit)
3. Klik tombol X (close) atau klik backdrop
4. Buka modal lagi

**Expected:**
- ✅ Semua jawaban sudah di-reset (kosong)
- ✅ Form kembali ke state awal
- ✅ Fetch questions lagi dari API

---

### Test 10: Responsive Design (Optional)

**Steps:**
1. Buka modal di desktop browser (full screen)
2. Resize browser ke mobile size (375px)
3. Atau buka DevTools → Toggle device toolbar (Ctrl+Shift+M)

**Expected:**
- ✅ Modal responsive
- ✅ Questions stack vertical di mobile
- ✅ Button tetap visible
- ✅ Scroll bekerja dengan baik

---

## 🐛 Common Issues & Solutions

### Issue 1: "Cannot read property 'questions' of null"

**Cause:** `surveyData` is null when trying to render

**Solution:**
- Check conditional rendering: `surveyData ? <form> : <error>`
- Make sure loading state is handled properly

---

### Issue 2: CORS Error in Console

**Symptom:**
```
Access to fetch at 'http://192.168.1.5:8000/api/survey/questions'
from origin 'http://localhost:3000' has been blocked by CORS policy
```

**Solution:**

1. Check Laravel `config/cors.php`:
```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_origins' => ['*'], // atau ['http://localhost:3000', 'http://localhost:3001']
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
```

2. Clear config:
```bash
php artisan config:clear
php artisan cache:clear
```

3. Restart Laravel server

---

### Issue 3: Questions Not Showing

**Checks:**

1. **API returning data?**
   ```bash
   curl http://192.168.1.5:8000/api/survey/questions
   ```

2. **Browser console errors?**
   - F12 → Console tab
   - Look for red errors

3. **Network tab:**
   - F12 → Network tab
   - Refresh modal
   - Look for `/survey/questions` request
   - Status 200? Response body correct?

4. **State not updating?**
   - Add `console.log(surveyData)` after `setSurveyData(result.data)`
   - Check if data is there

---

### Issue 4: Submit Returns 422 Validation Error

**Check:**

1. **Console log payload before submit:**
   ```typescript
   console.log('Payload:', payload);
   ```

2. **Backend validation error:**
   - Check Laravel logs: `storage/logs/laravel.log`
   - Which field failed validation?

3. **Common causes:**
   - `survey_template_id` missing or wrong
   - `responses` array empty
   - `question_id` doesn't exist
   - `option_id` doesn't exist
   - Required fields missing (usia, jenis_kelamin, dll)

---

### Issue 5: Success But Data Not Saved

**Check Database:**

```sql
-- Latest survey
SELECT * FROM surveys ORDER BY id DESC LIMIT 1;

-- Should have:
-- survey_template_id = 11 (not null!)
-- nama_responden, usia, etc filled

-- Responses
SELECT COUNT(*) FROM survey_responses
WHERE survey_id = [last_survey_id];

-- Count should match number of questions answered
```

**If survey_template_id is NULL:**
- Backend might be using old format
- Check `SurveyController::store()` validation
- Make sure payload has `survey_template_id`

---

## ✅ Testing Checklist Summary

Copy this to track your testing:

```
[ ] Modal opens with loading spinner
[ ] Questions fetched and displayed correctly
[ ] Questions sorted by urutan
[ ] Required questions marked with *
[ ] Multiple choice options clickable
[ ] Visual feedback on selection (blue border)
[ ] Text input questions show textarea (if any)
[ ] Required validation works
[ ] Age validation works (1-120)
[ ] Data diri validation works
[ ] Successful submission flow
[ ] Success message shows
[ ] Modal auto-closes after success
[ ] Data saved to database correctly
[ ] survey_template_id saved (not null)
[ ] survey_responses records created
[ ] Duplicate submission prevented
[ ] Error: no active template handled
[ ] Error: network error handled
[ ] Retry button works
[ ] Modal close resets form
[ ] ESC key closes modal
[ ] Backdrop click closes modal
[ ] Responsive on mobile
```

---

## 📊 Sample Test Data

Use this for consistent testing:

**Test Survey Submission 1:**
- Usia: 25
- Jenis Kelamin: Laki-laki
- Pendidikan: S1
- Pekerjaan: PNS
- All answers: Pilihan ke-4 (tertinggi)
- Saran: "Pelayanan sangat memuaskan"

**Test Survey Submission 2:**
- Usia: 45
- Jenis Kelamin: Perempuan
- Pendidikan: SMA/SMK
- Pekerjaan: Wiraswasta
- All answers: Pilihan ke-1 (terendah)
- Saran: "Perlu banyak perbaikan"

**Test Survey Submission 3:**
- Usia: 30
- Jenis Kelamin: Laki-laki
- Pendidikan: D3
- Pekerjaan: Pegawai Swasta
- Mixed answers: 2, 3, 4, 3, 4, 3, 4, 3, 4
- Saran: (kosong)

---

## 🎯 Next Steps After Testing

### If All Tests Pass ✅

1. Mark todo as complete
2. Create production build:
   ```bash
   npm run build
   ```
3. Test production build locally:
   ```bash
   npm run start # or serve build folder
   ```
4. Move to Flutter implementation
5. Plan deployment

### If Tests Fail ❌

1. Document the issue
2. Check console errors
3. Check network tab
4. Check backend logs
5. Report issue with:
   - Steps to reproduce
   - Expected vs actual behavior
   - Console errors
   - Network response
   - Backend logs (if relevant)

---

## 📞 Support

If stuck, check:
1. **TROUBLESHOOTING.md** - Common errors & solutions
2. **REACT_IMPLEMENTATION_DONE.md** - Implementation details
3. **REACT_SURVEY_UPDATE_GUIDE.md** - Full code reference

---

**Last Updated:** 2025-12-17
**Ready for:** User acceptance testing (UAT)
