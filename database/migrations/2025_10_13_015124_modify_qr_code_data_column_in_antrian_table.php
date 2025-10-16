<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('antrian', function (Blueprint $table) {
            $table->longText('qr_code_data')->change();
        });
    }

    public function down(): void
    {
        Schema::table('antrian', function (Blueprint $table) {
            // Ubah kembali ke VARCHAR(255) jika rollback
            $table->string('qr_code_data', 255)->change();
        });
    }
};
