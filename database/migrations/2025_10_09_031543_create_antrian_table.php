<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('antrian', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('konsultasi_id')->nullable(); // relasi opsional
            $table->string('nama');
            $table->string('no_hp');
            $table->string('alamat');
            $table->string('bidang_layanan');
            $table->string('layanan');
            $table->date('tanggal_daftar');
            $table->string('nomor_antrian')->nullable();
            $table->longText('qr_code_data')->nullable();
            $table->enum('status', ['Diproses', 'Selesai', 'Batal'])->default('Diproses');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('antrian');
    }
};
