<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('standar_pelayanan', function (Blueprint $table) {
            $table->id();
            $table->string('bidang')->index();      // contoh: "Bagian Tata Usaha"
            $table->string('layanan')->index();     // contoh: "Permohonan Audiensi"
            $table->string('file_path')->nullable(); // path di disk 'public', ex: "standar-pelayanan/file.pdf"
            $table->timestamps();

            // pastikan kombinasi bidang+layanan unik agar tidak ada duplikat
            $table->unique(['bidang', 'layanan'], 'standar_bidang_layanan_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('standar_pelayanan');
    }
};
