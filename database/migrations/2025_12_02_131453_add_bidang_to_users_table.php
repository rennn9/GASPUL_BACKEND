<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('bidang', [
                'Bagian Tata Usaha',
                'Bidang Bimbingan Masyarakat Islam',
                'Bidang Pendidikan Madrasah',
                'Bimas Kristen',
                'Bimas Katolik',
                'Bimas Hindu',
                'Bimas Buddha'
            ])->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('bidang');
        });
    }
};
