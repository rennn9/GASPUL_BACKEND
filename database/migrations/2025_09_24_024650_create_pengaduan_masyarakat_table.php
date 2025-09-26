<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaduan_masyarakat', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('nip');
            $table->string('jenis_laporan'); // Korupsi, Asusila, Gratifikasi, Dll
            $table->text('penjelasan');
            $table->string('file')->nullable(); // nama file jika diupload
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaduan_masyarakat');
    }
};
