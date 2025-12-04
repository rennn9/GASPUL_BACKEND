<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::table('layanan_publik', function (Blueprint $table) {
        $table->string('nama')->nullable();
        $table->string('email')->nullable();
        $table->string('telepon')->nullable();
    });
}

public function down()
{
    Schema::table('layanan_publik', function (Blueprint $table) {
        $table->dropColumn(['nama', 'email', 'telepon']);
    });
}

};
