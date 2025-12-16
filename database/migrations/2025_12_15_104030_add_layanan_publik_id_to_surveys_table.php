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
        Schema::table('surveys', function (Blueprint $table) {
            // Add foreign key to layanan_publik
            $table->unsignedBigInteger('layanan_publik_id')->nullable()->after('antrian_id');
            $table->foreign('layanan_publik_id')
                ->references('id')
                ->on('layanan_publik')
                ->onDelete('cascade');

            // Add unique constraint to prevent duplicate surveys per layanan
            $table->unique('layanan_publik_id', 'surveys_layanan_publik_id_unique');

            // Add timestamp for when survey was completed
            $table->timestamp('surveyed_at')->nullable()->after('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->dropForeign(['layanan_publik_id']);
            $table->dropUnique('surveys_layanan_publik_id_unique');
            $table->dropColumn(['layanan_publik_id', 'surveyed_at']);
        });
    }
};
