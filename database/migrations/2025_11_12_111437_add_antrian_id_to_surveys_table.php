<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->unsignedBigInteger('antrian_id')->nullable()->after('id');
            $table->foreign('antrian_id')->references('id')->on('antrian')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->dropForeign(['antrian_id']);
            $table->dropColumn('antrian_id');
        });
    }
};
