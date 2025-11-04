<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('antrian', function (Blueprint $table) {
            $table->unsignedBigInteger('konsultasi_id')->nullable()->after('id');
            $table->foreign('konsultasi_id')->references('id')->on('konsultasi')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('antrian', function (Blueprint $table) {
            $table->dropForeign(['konsultasi_id']);
            $table->dropColumn('konsultasi_id');
        });
    }
};
