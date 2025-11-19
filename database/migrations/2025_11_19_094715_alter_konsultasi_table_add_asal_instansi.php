<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('konsultasi', function (Blueprint $table) {
            // Hapus kolom
            if (Schema::hasColumn('konsultasi', 'isi_konsultasi')) {
                $table->dropColumn('isi_konsultasi');
            }

            // Tambah kolom baru setelah alamat
            $table->string('asal_instansi')->nullable()->after('alamat');
        });
    }

    public function down(): void
    {
        Schema::table('konsultasi', function (Blueprint $table) {
            // Kembalikan kolom isi_konsultasi
            $table->text('isi_konsultasi')->nullable();

            // Hapus kolom baru
            $table->dropColumn('asal_instansi');
        });
    }
};
