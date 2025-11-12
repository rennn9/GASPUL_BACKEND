<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateKonsultasiTable extends Migration
{
    public function up()
    {
        // Tambah kolom baru jika belum ada, dan ganti nama kolom lama bila ada
        // NOTE: renameColumn memerlukan doctrine/dbal
        Schema::table('konsultasi', function (Blueprint $table) {
            // Tambah alamat (nullable)
            if (! Schema::hasColumn('konsultasi', 'alamat')) {
                $table->text('alamat')->nullable()->after('email');
            }

            // Tambah nomor_antrian (nullable)
            if (! Schema::hasColumn('konsultasi', 'nomor_antrian')) {
                $table->string('nomor_antrian')->nullable()->after('dokumen');
            }

            // Jika belum ada kolom no_hp_wa, dan ada kolom no_hp -> rename
            if (! Schema::hasColumn('konsultasi', 'no_hp_wa') && Schema::hasColumn('konsultasi', 'no_hp')) {
                $table->renameColumn('no_hp', 'no_hp_wa');
            }

            // Rename tanggal_konsultasi -> tanggal_layanan
            if (! Schema::hasColumn('konsultasi', 'tanggal_layanan') && Schema::hasColumn('konsultasi', 'tanggal_konsultasi')) {
                $table->renameColumn('tanggal_konsultasi', 'tanggal_layanan');
            }
        });
    }

    public function down()
    {
        Schema::table('konsultasi', function (Blueprint $table) {
            // Kembalikan rename jika ada
            if (Schema::hasColumn('konsultasi', 'no_hp_wa') && ! Schema::hasColumn('konsultasi', 'no_hp')) {
                $table->renameColumn('no_hp_wa', 'no_hp');
            }

            if (Schema::hasColumn('konsultasi', 'tanggal_layanan') && ! Schema::hasColumn('konsultasi', 'tanggal_konsultasi')) {
                $table->renameColumn('tanggal_layanan', 'tanggal_konsultasi');
            }

            // Hapus kolom yang kita tambahkan
            if (Schema::hasColumn('konsultasi', 'alamat')) {
                $table->dropColumn('alamat');
            }
            if (Schema::hasColumn('konsultasi', 'nomor_antrian')) {
                $table->dropColumn('nomor_antrian');
            }
        });
    }
}
