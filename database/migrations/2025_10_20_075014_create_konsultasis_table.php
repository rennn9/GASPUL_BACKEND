<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('konsultasis', function (Blueprint $table) {
            $table->id();
            $table->string('nama_lengkap');
            $table->string('no_hp', 20);
            $table->string('email')->nullable();
            $table->string('perihal');
            $table->text('isi_konsultasi');
            $table->string('dokumen')->nullable();
            $table->enum('status', ['baru', 'proses', 'selesai', 'batal'])->default('baru');
            $table->dateTime('tanggal_konsultasi');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('konsultasis');
    }
};
