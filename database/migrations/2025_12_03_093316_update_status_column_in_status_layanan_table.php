<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('status_layanan', function (Blueprint $table) {
            $table->string('status', 50)->change();
        });
    }

    public function down()
    {
        Schema::table('status_layanan', function (Blueprint $table) {
            $table->string('status', 20)->change(); // sesuaikan jika sebelumnya 20
        });
    }
};
