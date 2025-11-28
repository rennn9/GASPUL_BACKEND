<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('layanan_publik', function (Blueprint $table) {
            $table->id();
            $table->string('nik', 20);
            $table->string('no_registrasi', 50)->unique();
            $table->string('bidang');
            $table->string('layanan');
            $table->string('berkas')->nullable(); // path file upload
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('layanan_publik');
    }
};
