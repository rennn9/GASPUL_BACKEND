<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAntrianTable extends Migration
{
    public function up()
    {
        Schema::table('antrian', function (Blueprint $table) {
            // Tambah email jika belum ada
            if (! Schema::hasColumn('antrian', 'email')) {
                $table->string('email')->nullable()->after('no_hp');
            }

            // Rename no_hp -> no_hp_wa jika diperlukan
            if (! Schema::hasColumn('antrian', 'no_hp_wa') && Schema::hasColumn('antrian', 'no_hp')) {
                $table->renameColumn('no_hp', 'no_hp_wa');
            }

            // Rename tanggal_daftar -> tanggal_layanan
            if (! Schema::hasColumn('antrian', 'tanggal_layanan') && Schema::hasColumn('antrian', 'tanggal_daftar')) {
                $table->renameColumn('tanggal_daftar', 'tanggal_layanan');
            }
        });
    }

    public function down()
    {
        Schema::table('antrian', function (Blueprint $table) {
            // Kembalikan rename jika ada
            if (Schema::hasColumn('antrian', 'no_hp_wa') && ! Schema::hasColumn('antrian', 'no_hp')) {
                $table->renameColumn('no_hp_wa', 'no_hp');
            }

            if (Schema::hasColumn('antrian', 'tanggal_layanan') && ! Schema::hasColumn('antrian', 'tanggal_daftar')) {
                $table->renameColumn('tanggal_layanan', 'tanggal_daftar');
            }

            // Hapus email bila kita tambahkan sebelumnya
            if (Schema::hasColumn('antrian', 'email')) {
                $table->dropColumn('email');
            }
        });
    }
}
