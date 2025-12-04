<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{
    Schema::create('status_layanan', function (Blueprint $table) {
        $table->id();
        $table->foreignId('layanan_id')->constrained('layanan_publik')->onDelete('cascade');
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
        $table->enum('status', ['Diterima','Ditolak','Perlu Perbaikan','Selesai','Perbaikan Selesai']);
        $table->text('keterangan')->nullable();
        $table->json('file_surat')->nullable();
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('status_layanan');
}
};
