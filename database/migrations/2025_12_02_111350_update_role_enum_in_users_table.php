<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah role baru ke ENUM
        DB::statement("ALTER TABLE users MODIFY role ENUM('superadmin', 'admin', 'operator', 'operator_bidang', 'user') DEFAULT 'admin'");
    }

    public function down(): void
    {
        // Rollback ke role sebelumnya
        DB::statement("ALTER TABLE users MODIFY role ENUM('superadmin', 'admin', 'operator') DEFAULT 'admin'");
    }
};
