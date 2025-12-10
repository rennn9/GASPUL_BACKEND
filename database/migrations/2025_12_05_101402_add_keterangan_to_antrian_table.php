<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up()
{
    Schema::table('antrian', function (Blueprint $table) {
        $table->text('keterangan')->nullable();
    });
}

public function down()
{
    Schema::table('antrian', function (Blueprint $table) {
        $table->dropColumn('keterangan');
    });
}

};
