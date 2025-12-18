# 🚀 Frontend Quick Start Guide

## Overview

Panduan ringkas untuk mengintegrasikan frontend (Flutter & React) dengan backend survey dinamis.

**Backend Status:** ✅ Ready (API endpoints tested and working)

**API Endpoints Available:**
- `GET /api/survey/questions` - Fetch survey questions
- `POST /api/survey` - Submit survey (dual format support)

---

## 📱 Flutter Implementation

### Step 1: Lokasi Project Flutter

Cari folder project Flutter Anda (kemungkinan):
```
C:/Users/wijay/Documents/gaspul/
atau
C:/Users/wijay/Desktop/gaspul/
atau
C:/path/to/your/flutter-project/
```

### Step 2: File yang Perlu Dibuat/Edit

#### **A. Create Survey Models** (File Baru)
**Path:** `lib/core/models/survey_models.dart`

**Action:** Copy semua code dari **FLUTTER_SURVEY_UPDATE_GUIDE.md** section "Survey Models"

**Isi:** 7 classes
- `SurveyTemplateResponse`
- `SurveyTemplateData`
- `SurveyTemplateInfo`
- `SurveyQuestion`
- `SurveyOption`
- `SurveySubmissionRequest`
- `SurveyResponseItem`

#### **B. Update Survey Service** (Edit Existing)
**Path:** `lib/core/services/survey_service.dart`

**Action:** Tambahkan 2 methods baru:
```dart
Future<SurveyTemplateResponse> fetchSurveyQuestions({int? templateId})
Future<Map<String, dynamic>> submitSurvey(SurveySubmissionRequest request)
```

**Copy dari:** FLUTTER_SURVEY_UPDATE_GUIDE.md section "Survey Service"

#### **C. Update Survey Page** (Edit Existing)
**Path:** `lib/features/home/survey_page.dart`

**Action:**
1. Hapus hardcoded questions array
2. Tambah state untuk loading dan data dari API
3. Add `_loadSurveyQuestions()` method
4. Update build method untuk handle loading/error

**Copy dari:** FLUTTER_SURVEY_UPDATE_GUIDE.md section "Survey Page"

#### **D. Update Question Step Widget** (Edit Existing)
**Path:** `lib/features/home/widgets/survey/question_step.dart`

**Action:**
1. Update untuk render dynamic questions
2. Handle text input vs multiple choice
3. Dynamic options rendering

**Copy dari:** FLUTTER_SURVEY_UPDATE_GUIDE.md section "Question Step"

### Step 3: Update Base URL

Cari di semua file service Anda, update base URL:
```dart
// Development
final String baseUrl = 'http://localhost:8000';

// Production
final String baseUrl = 'https://your-domain.com';
```

### Step 4: Test

```bash
cd /path/to/flutter-project
flutter pub get
flutter run
```

**Test Flow:**
1. Buka survey page
2. Lihat loading indicator
3. Pertanyaan muncul dari API
4. Isi survey
5. Submit
6. Check di backend apakah data masuk

---

## ⚛️ React Implementation

### Step 1: Lokasi Project React

Cari folder project React Anda (kemungkinan):
```
C:/Users/wijay/Documents/gaspul-web/
atau
C:/Users/wijay/Desktop/gaspul-web/
atau
C:/path/to/your/react-project/
```

### Step 2: File yang Perlu Dibuat/Edit

#### **A. Update ModalSurvey Component** (Edit Existing)
**Path:** `src/components/ui/ModalSurvey.tsx`

**Action:**
1. Hapus hardcoded `SURVEY_QUESTIONS` array
2. Tambah interfaces untuk TypeScript
3. Tambah state untuk data dari API
4. Tambah `fetchSurveyQuestions()` function
5. Update render untuk dynamic questions

**Copy seluruh file dari:** REACT_SURVEY_UPDATE_GUIDE.md

#### **B. Update CSS** (Optional)
**Path:** `src/components/ui/ModalSurvey.css` atau global CSS

**Copy dari:** REACT_SURVEY_UPDATE_GUIDE.md section "CSS Styles"

### Step 3: Environment Variables

**Create/Update:** `.env` file di root project:

```env
# Development
REACT_APP_API_BASE_URL=http://localhost:8000

# Production (update saat deploy)
# REACT_APP_API_BASE_URL=https://your-domain.com
```

### Step 4: Install Dependencies (jika belum)

```bash
cd /path/to/react-project
npm install axios
# atau
yarn add axios
```

### Step 5: Test

```bash
npm start
# atau
yarn start
```

**Test Flow:**
1. Buka halaman dengan modal survey
2. Click button buka survey
3. Lihat loading state
4. Pertanyaan muncul dari API
5. Isi survey
6. Submit
7. Check di backend apakah data masuk

---

## 🔧 Common Issues & Solutions

### Issue 1: CORS Error

**Symptoms:**
```
Access to XMLHttpRequest blocked by CORS policy
```

**Solution:**

Update Laravel `config/cors.php`:
```php
'paths' => ['api/*'],

'allowed_origins' => [
    'http://localhost:3000',  // React dev server
    'http://localhost:8080',
    env('FRONTEND_URL'),
],

'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
```

Then:
```bash
php artisan config:clear
```

---

### Issue 2: API Returns 404

**Check:**
1. Backend running? `php artisan serve`
2. URL correct? `http://localhost:8000/api/survey/questions`
3. Route registered? `php artisan route:list --path=survey`

---

### Issue 3: "No active template found"

**Solution:**

Activate a template via admin panel or SQL:
```sql
UPDATE survey_templates SET is_active = 0;  -- deactivate all
UPDATE survey_templates SET is_active = 1 WHERE id = 1;  -- activate template 1
```

Or via Tinker:
```bash
php artisan tinker
```
```php
App\Models\SurveyTemplate::find(1)->update(['is_active' => true]);
```

---

### Issue 4: Validation Error 422

**Common Causes:**
- Missing required fields
- Wrong data type (string vs integer)
- Invalid `survey_template_id`

**Debug:**
Check Laravel logs:
```bash
tail -f storage/logs/laravel.log
```

---

## 📋 Testing Checklist

### Backend (Already Done ✅)
- [x] GET /api/survey/questions works
- [x] POST /api/survey (new format) works
- [x] POST /api/survey (legacy format) works
- [x] Template activated

### Flutter Frontend
- [ ] API fetch questions works
- [ ] Loading state shows
- [ ] Questions render correctly
- [ ] Options render correctly
- [ ] Text input works (if any)
- [ ] Validation works
- [ ] Submit works
- [ ] Success message shows
- [ ] Data masuk ke backend

### React Frontend
- [ ] API fetch questions works
- [ ] Loading spinner shows
- [ ] Questions render correctly
- [ ] Options render correctly
- [ ] Text input works (if any)
- [ ] Navigation (prev/next) works
- [ ] Validation works
- [ ] Submit works
- [ ] Success message shows
- [ ] Data masuk ke backend

---

## 🎯 Minimal Implementation (If Time Limited)

Jika waktu terbatas, implementasi minimal:

### Flutter Minimal:
1. ✅ Create `survey_models.dart` - Copy paste full
2. ✅ Add `fetchSurveyQuestions()` to service - Just the method
3. ✅ Update survey_page to fetch API - Replace hardcoded with API call
4. ✅ Keep existing UI - No need to change much

### React Minimal:
1. ✅ Update ModalSurvey.tsx - Replace hardcoded with fetch
2. ✅ Add .env with API_BASE_URL
3. ✅ Keep existing CSS - No changes needed

**Total time:** ~1-2 hours per platform

---

## 📚 Full Documentation Reference

Untuk detail lengkap, lihat:
1. **FLUTTER_SURVEY_UPDATE_GUIDE.md** - Full Flutter code
2. **REACT_SURVEY_UPDATE_GUIDE.md** - Full React code
3. **IMPLEMENTATION_SUMMARY.md** - Overview
4. **TROUBLESHOOTING.md** - Error solutions

---

## 🚀 Next Steps After Frontend Done

1. **Integration Testing**
   - Test survey submission from Flutter → Backend
   - Test survey submission from React → Backend
   - Verify data di database

2. **Admin Panel Testing**
   - Check submitted surveys di `/admin/survey`
   - Check statistics di `/admin/statistik/survey`
   - Download Excel export

3. **Production Deployment**
   - Deploy backend ke server
   - Update .env API URLs di frontend
   - Deploy Flutter & React apps
   - Monitor error logs

---

## 💡 Tips

### Development
- Test API dengan Postman/cURL dulu sebelum frontend
- Use Laravel logs untuk debug: `tail -f storage/logs/laravel.log`
- Enable `APP_DEBUG=true` di .env saat development

### Git
```bash
# Commit backend changes first
git add .
git commit -m "feat: implement dynamic survey system backend"

# Then frontend changes
git add .
git commit -m "feat: integrate frontend with dynamic survey API"
```

### Backup
- Backup database sebelum deploy production
- Export backup: `php artisan survey:export-old-data`

---

## 📞 Need Help?

1. Check **TROUBLESHOOTING.md** untuk common errors
2. Check Laravel logs: `storage/logs/laravel.log`
3. Check browser console untuk frontend errors
4. Test API endpoints dengan cURL/Postman

---

**Last Updated:** 2025-12-17
**Status:** Backend Ready ✅ | Frontend Guides Available ✅
