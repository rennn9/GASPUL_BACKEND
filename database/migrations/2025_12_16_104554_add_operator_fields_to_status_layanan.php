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
        Schema::table('status_layanan', function (Blueprint $table) {
            $table->string('operator_nama', 255)->nullable()->after('user_id');
            $table->string('operator_no_hp', 20)->nullable()->after('operator_nama');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('status_layanan', function (Blueprint $table) {
            $table->dropColumn(['operator_nama', 'operator_no_hp']);
        });
    }
};
