<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up()
{
    Schema::table('status_layanan', function (Blueprint $table) {
        $table->string('file_perbaikan')->nullable()->after('file_surat');
    });
}

public function down()
{
    Schema::table('status_layanan', function (Blueprint $table) {
        $table->dropColumn('file_perbaikan');
    });
}
};
