# 🔧 Troubleshooting Guide

## Error: "Integrity constraint violation: 1452 Cannot add or update a child row"

### Symptoms
```
SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row:
a foreign key constraint fails (`gaspul_db`.`survey_templates`,
CONSTRAINT `survey_templates_created_by_user_id_foreign`
REFERENCES `users` (`id`) ON DELETE SET NULL)
```

### Root Cause
The `created_by_user_id` value does not exist in the `users` table, atau Anda belum login ke admin panel.

### Solutions

#### Solution 1: Login ke Admin Panel (Recommended)
1. Pastikan Anda sudah **login** sebelum membuat template
2. Akses: `http://localhost:8000/login`
3. Login dengan kredensial admin/superadmin
4. Setelah login, baru akses `/admin/survey-templates`

#### Solution 2: Verifikasi User ID
Jika sudah login tapi masih error, check user ID yang login:

```php
// Di controller, tambahkan log
Log::info('Auth User ID:', ['user_id' => auth()->id()]);
```

#### Solution 3: Code Fix (Already Applied) ✅
Code sudah di-update dengan fallback mechanism:

```php
// Get authenticated user ID, or use first admin user as fallback
$userId = auth()->id();
if (!$userId) {
    // Fallback: get first superadmin or admin user
    $userId = \App\Models\User::whereIn('role', ['superadmin', 'admin'])->first()?->id;
}
```

**Lokasi fix:**
- `SurveyTemplateController::store()` line 60-65
- `SurveyTemplateController::duplicate()` line 262-267

#### Solution 4: Manual Test (Development Only)
Jika testing di development tanpa login, gunakan Tinker:

```php
php artisan tinker
```

```php
$template = App\Models\SurveyTemplate::create([
    'nama' => 'Test Template',
    'deskripsi' => 'Testing tanpa login',
    'versi' => 2,
    'is_active' => false,
    'created_by_user_id' => 1 // Hardcoded to admin user
]);
```

---

## Error: "No active template found" (API)

### Symptoms
```json
{
  "success": false,
  "message": "Tidak ada template survey aktif saat ini.",
  "data": null
}
```

### Solution
Aktivasi template melalui admin panel atau via Tinker:

```php
php artisan tinker
```

```php
// Deactivate all
App\Models\SurveyTemplate::where('is_active', true)->update(['is_active' => false]);

// Activate template ID 1
App\Models\SurveyTemplate::find(1)->update(['is_active' => true]);
```

Atau via SQL:

```sql
UPDATE survey_templates SET is_active = 0;  -- deactivate all
UPDATE survey_templates SET is_active = 1 WHERE id = 1;  -- activate template 1
```

---

## Error: "Route [admin.survey-templates.index] not defined"

### Solution
Clear all caches:

```bash
php artisan route:clear
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

---

## Error: "Class 'App\Models\SurveyTemplate' not found"

### Solutions

#### 1. Check File Exists
```bash
ls app/Models/SurveyTemplate.php
```

#### 2. Clear Autoload Cache
```bash
composer dump-autoload
```

#### 3. Verify Namespace
File `app/Models/SurveyTemplate.php` should have:
```php
<?php

namespace App\Models;

class SurveyTemplate extends Model
{
    // ...
}
```

---

## Error: "Access denied" saat akses Admin Panel

### Symptoms
- 403 Forbidden
- Redirect ke halaman error
- "Unauthorized action"

### Solution

#### Check User Role
```bash
php check_users.php
```

Output expected:
```
ID: 1, Name: Administrator, Role: superadmin
```

#### Verify Middleware
Routes `/admin/survey-templates/*` require:
- `auth` middleware (must be logged in)
- `role:superadmin,admin` middleware

Jika user role Anda bukan `superadmin` atau `admin`, Anda tidak bisa akses.

#### Update User Role (Development Only)
```php
php artisan tinker
```

```php
$user = App\Models\User::find(YOUR_USER_ID);
$user->role = 'admin';
$user->save();
```

---

## Error: "SQLSTATE[42S22]: Column not found: 1054 Unknown column 'email'"

### Root Cause
Tabel `users` di database ini tidak punya kolom `email`.

### Solution
Jangan gunakan kolom `email` saat query users:

```php
// BAD
User::select('id', 'name', 'email')->get();

// GOOD
User::select('id', 'name', 'role')->get();
```

---

## Admin Panel Tidak Muncul Setelah Login

### Checklist
1. Pastikan sudah login dengan user yang benar
2. Check session di browser (F12 > Application > Cookies)
3. Verify route:
   ```
   http://localhost:8000/admin/survey-templates
   ```
4. Check Laravel logs:
   ```bash
   tail -f storage/logs/laravel.log
   ```

---

## API Endpoint Returns 500 Error

### Debug Steps

#### 1. Check Laravel Logs
```bash
tail -f storage/logs/laravel.log
```

#### 2. Enable Debug Mode (Development Only)
`.env`:
```
APP_DEBUG=true
```

#### 3. Test with cURL
```bash
curl -v http://localhost:8000/api/survey/questions
```

#### 4. Check Database Connection
```bash
php artisan tinker
```

```php
DB::connection()->getPdo();
// Should not throw error
```

---

## Migration Rollback Issues

### Error: "Cannot rollback, surveys exist"

This is **BY DESIGN** (data protection). Cannot delete template with surveys.

### Solution A: Delete Surveys First
```sql
DELETE FROM survey_responses WHERE survey_id > 9;
DELETE FROM surveys WHERE id > 9;
```

### Solution B: Use Rollback Command
```bash
php artisan survey:rollback
```

Akan restore dari backup JSON dan rollback migrations.

---

## Performance Issues

### Slow Loading Time

#### Check Query Performance
Add to `AppServiceProvider::boot()`:

```php
DB::listen(function ($query) {
    if ($query->time > 100) { // > 100ms
        Log::warning('Slow Query', [
            'sql' => $query->sql,
            'time' => $query->time
        ]);
    }
});
```

#### Enable Query Caching
```php
$templates = Cache::remember('survey_templates', 3600, function () {
    return SurveyTemplate::with('createdBy')->get();
});
```

---

## Frontend Integration Issues

### CORS Error

#### Symptoms
```
Access to XMLHttpRequest at 'http://localhost:8000/api/survey/questions'
from origin 'http://localhost:3000' has been blocked by CORS policy
```

#### Solution
Update `config/cors.php`:

```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],

'allowed_origins' => [
    'http://localhost:3000', // React
    'http://localhost:8080', // Other
],

'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
```

Then clear config:
```bash
php artisan config:clear
```

---

## Common Development Commands

### Check Everything is OK
```bash
# Migrations
php artisan migrate:status | grep survey

# Test Migration
php artisan survey:test-migration

# Routes
php artisan route:list --path=survey

# Check Users
php check_users.php

# Clear All Caches
php artisan optimize:clear
```

### Fresh Start (Development Only - DESTRUCTIVE)
```bash
# WARNING: This will delete ALL survey data!

# Rollback
php artisan survey:rollback

# Or manual rollback
php artisan migrate:rollback --step=5

# Fresh migration
php artisan migrate
php artisan db:seed --class=SurveyTemplateMigrationSeeder
```

---

## Production Checklist

Before deploying to production:

- [ ] Backup database
- [ ] Test all migrations in staging
- [ ] Verify rollback mechanism works
- [ ] Test admin panel CRUD
- [ ] Test API endpoints
- [ ] Test frontend integration
- [ ] Check permissions (file & folder)
- [ ] Enable production mode (`APP_DEBUG=false`)
- [ ] Clear all caches
- [ ] Monitor error logs after deployment

---

## Support

If issue persists:
1. Check `storage/logs/laravel.log`
2. Enable debug mode (`.env` → `APP_DEBUG=true`)
3. Test with sample data
4. Review error trace carefully
5. Check database connections

For more help, review:
- `IMPLEMENTATION_SUMMARY.md`
- `QUICK_REFERENCE.md`
- `TESTING_REPORT.md`
