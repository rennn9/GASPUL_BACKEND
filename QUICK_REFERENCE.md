# 🚀 Quick Reference - Sistem Survey Dinamis

## 📋 Table of Contents
1. [Artisan Commands](#artisan-commands)
2. [API Testing](#api-testing)
3. [Database Queries](#database-queries)
4. [Common Tasks](#common-tasks)
5. [Troubleshooting](#troubleshooting)

---

## Artisan Commands

### Migration & Data Management
```bash
# Export backup data lama (wajib dilakukan sebelum migration)
php artisan survey:export-old-data

# Run migrations
php artisan migrate

# Seed data lama ke sistem baru
php artisan db:seed --class=SurveyTemplateMigrationSeeder

# Test hasil migration
php artisan survey:test-migration

# Rollback ke data lama (emergency only)
php artisan survey:rollback
```

### Development Commands
```bash
# Clear all caches
php artisan route:clear
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# View all survey routes
php artisan route:list | grep survey

# Tinker (database exploration)
php artisan tinker
```

---

## API Testing

### 1. Fetch Survey Questions (GET)
```bash
# Get active template
curl -X GET "http://localhost:8000/api/survey/questions" \
     -H "Accept: application/json"

# Get specific template
curl -X GET "http://localhost:8000/api/survey/questions?template_id=2" \
     -H "Accept: application/json"
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "template": {
      "id": 1,
      "nama": "Template IKM 2024",
      "versi": 1,
      "deskripsi": null
    },
    "questions": [
      {
        "id": 1,
        "pertanyaan": "Bagaimana pendapat Saudara...",
        "kode_unsur": "U1",
        "urutan": 1,
        "is_required": true,
        "is_text_input": false,
        "options": [
          {"id": 1, "jawaban": "Tidak sesuai", "poin": 1, "urutan": 1},
          {"id": 2, "jawaban": "Kurang sesuai", "poin": 2, "urutan": 2}
        ]
      }
    ]
  }
}
```

---

### 2. Submit Survey - NEW FORMAT (POST)
```bash
curl -X POST "http://localhost:8000/api/survey" \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     -d '{
       "survey_template_id": 1,
       "nama_responden": "John Doe",
       "no_hp_wa": "081234567890",
       "usia": 35,
       "jenis_kelamin": "Laki-laki",
       "pendidikan": "S1",
       "pekerjaan": "PNS",
       "bidang": "Bidang Bimas Islam",
       "responses": [
         {"question_id": 1, "option_id": 4, "poin": 4},
         {"question_id": 2, "option_id": 8, "poin": 4},
         {"question_id": 10, "text_answer": "Saran saya adalah..."}
       ],
       "saran": "Pelayanan bagus"
     }'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Survey berhasil disimpan.",
  "data": {
    "id": 10,
    "survey_template_id": 1,
    "nama_responden": "John Doe",
    "responses": [...]
  }
}
```

---

### 3. Submit Survey - OLD FORMAT (POST - Backward Compatible)
```bash
curl -X POST "http://localhost:8000/api/survey" \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     -d '{
       "nama_responden": "Jane Doe",
       "usia": 30,
       "jenis_kelamin": "Perempuan",
       "pendidikan": "S1",
       "pekerjaan": "Swasta",
       "jawaban": [
         {"jawaban": "Sesuai", "nilai": 3},
         {"jawaban": "Mudah", "nilai": 3}
       ],
       "saran": "Baik"
     }'
```

---

## Database Queries

### Check Migration Status
```sql
-- Cek template yang sudah dibuat
SELECT * FROM survey_templates;

-- Cek pertanyaan template tertentu
SELECT * FROM survey_questions WHERE survey_template_id = 1 ORDER BY urutan;

-- Cek pilihan jawaban
SELECT sq.pertanyaan_text, sqo.jawaban_text, sqo.poin
FROM survey_questions sq
JOIN survey_question_options sqo ON sq.id = sqo.survey_question_id
WHERE sq.survey_template_id = 1
ORDER BY sq.urutan, sqo.urutan;

-- Cek survey yang sudah migrate
SELECT COUNT(*) as total, survey_template_id
FROM surveys
GROUP BY survey_template_id;

-- Cek responses
SELECT COUNT(*) FROM survey_responses;

-- Detail response 1 survey
SELECT s.nama_responden, sq.pertanyaan_text, sr.jawaban_text, sr.poin
FROM surveys s
JOIN survey_responses sr ON s.id = sr.survey_id
JOIN survey_questions sq ON sr.survey_question_id = sq.id
WHERE s.id = 1;
```

### Verify Data Integrity
```sql
-- Cek survey tanpa template_id (legacy)
SELECT COUNT(*) FROM surveys WHERE survey_template_id IS NULL;

-- Cek survey dengan template_id
SELECT COUNT(*) FROM surveys WHERE survey_template_id IS NOT NULL;

-- Cek template aktif
SELECT * FROM survey_templates WHERE is_active = 1;

-- Cek pertanyaan tanpa kode_unsur (non-IKM)
SELECT * FROM survey_questions WHERE kode_unsur IS NULL;
```

---

## Common Tasks

### 1. Activate a Template
```sql
-- Via SQL
UPDATE survey_templates SET is_active = 0; -- deactivate all
UPDATE survey_templates SET is_active = 1 WHERE id = 2; -- activate specific
```

```php
// Via Tinker
php artisan tinker
>>> App\Models\SurveyTemplate::where('is_active', true)->update(['is_active' => false]);
>>> App\Models\SurveyTemplate::find(2)->update(['is_active' => true]);
```

---

### 2. Create Template via Tinker
```php
php artisan tinker

// Create template
$template = App\Models\SurveyTemplate::create([
    'nama' => 'Template IKM 2025',
    'deskripsi' => 'Survey kepuasan 2025',
    'versi' => 2,
    'is_active' => false,
    'created_by_user_id' => 1
]);

// Add question
$question = $template->questions()->create([
    'pertanyaan_text' => 'Bagaimana pelayanan kami?',
    'kode_unsur' => 'U1',
    'urutan' => 1,
    'is_required' => true,
    'is_text_input' => false
]);

// Add options
$question->options()->create(['jawaban_text' => 'Buruk', 'poin' => 1, 'urutan' => 1]);
$question->options()->create(['jawaban_text' => 'Cukup', 'poin' => 2, 'urutan' => 2]);
$question->options()->create(['jawaban_text' => 'Baik', 'poin' => 3, 'urutan' => 3]);
$question->options()->create(['jawaban_text' => 'Sangat Baik', 'poin' => 4, 'urutan' => 4]);
```

---

### 3. Bulk Delete Test Data
```sql
-- HATI-HATI! Ini menghapus semua data survey testing
DELETE FROM survey_responses WHERE survey_id > 9;
DELETE FROM surveys WHERE id > 9;
```

---

### 4. Export Current Template to JSON
```php
php artisan tinker

$template = App\Models\SurveyTemplate::with('questions.options')->find(1);
$json = json_encode($template, JSON_PRETTY_PRINT);
file_put_contents('template_backup.json', $json);
echo "Exported to template_backup.json";
```

---

## Troubleshooting

### Error: "Route [admin.survey-templates.index] not defined"
```bash
php artisan route:clear
php artisan config:clear
php artisan cache:clear
```

---

### Error: "Call to undefined relationship [creator]"
**Fix:** Update controller to use `createdBy` instead of `creator`
```php
// BAD
$templates = SurveyTemplate::with('creator')->get();

// GOOD
$templates = SurveyTemplate::with('createdBy')->get();
```

---

### Error: "SQLSTATE[23000]: Integrity constraint violation"
**Cause:** Foreign key constraint
**Fix:** Pastikan data parent ada sebelum insert child
```sql
-- Check if template exists
SELECT * FROM survey_templates WHERE id = 1;

-- Check if question exists
SELECT * FROM survey_questions WHERE id = 10;
```

---

### Error: "No active template found"
```sql
-- Check active templates
SELECT * FROM survey_templates WHERE is_active = 1;

-- Activate one
UPDATE survey_templates SET is_active = 1 WHERE id = 1;
```

---

### Performance Issue: Slow loading
```php
// Bad (N+1 query problem)
$surveys = Survey::all();
foreach($surveys as $survey) {
    echo $survey->template->nama; // N queries
}

// Good (Eager loading)
$surveys = Survey::with('template')->get();
foreach($surveys as $survey) {
    echo $survey->template->nama; // 1 query
}
```

---

### Migration Rollback Failed
```bash
# Manual rollback
php artisan migrate:rollback --step=5

# Or restore from backup
php artisan survey:rollback
```

---

## Admin Panel URLs

```
# Template Management
http://localhost:8000/admin/survey-templates

# Question Management (replace {id} with template ID)
http://localhost:8000/admin/survey-questions/1

# Statistics
http://localhost:8000/admin/statistik/survey

# Survey List
http://localhost:8000/admin/survey
```

---

## File Locations

### Backend
```
app/Http/Controllers/
├── SurveyTemplateController.php
├── SurveyQuestionController.php
├── SurveyController.php
└── Api/SurveyApiController.php

app/Models/
├── SurveyTemplate.php
├── SurveyQuestion.php
├── SurveyQuestionOption.php
└── SurveyResponse.php

resources/views/admin/
├── survey-template/
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   └── preview.blade.php
└── survey-question/
    └── index.blade.php
```

### Backup
```
storage/app/backups/
└── survey_backup_YYYYMMDD_HHMMSS.json
```

### Logs
```
storage/logs/
└── laravel.log
```

---

## Environment Variables

### Development
```env
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_DATABASE=gaspul_backend
```

### Production
```env
APP_URL=https://your-domain.com
APP_ENV=production
APP_DEBUG=false
```

---

## Git Commands (if needed)

```bash
# Check status
git status

# Stage changes
git add .

# Commit
git commit -m "Implement dynamic survey system"

# Push to remote
git push origin main

# Create backup branch before risky operations
git checkout -b backup-before-survey-implementation
git push origin backup-before-survey-implementation
```

---

## Quick Health Check

Run this checklist after deployment:

```bash
# 1. Check database migrations
php artisan migrate:status

# 2. Check if template exists
php artisan tinker
>>> App\Models\SurveyTemplate::count()

# 3. Test API endpoint
curl http://localhost:8000/api/survey/questions

# 4. Check Laravel logs
tail -f storage/logs/laravel.log

# 5. Check permissions
ls -la storage/app/backups

# 6. Test admin panel access
# Open browser: http://localhost:8000/admin/survey-templates
```

---

**Last Updated:** 2025-12-17
**Version:** 1.0.0
