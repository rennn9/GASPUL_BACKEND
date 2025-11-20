<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMonitorSettingsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('monitor_settings', function (Blueprint $table) {
            $table->id();

            // URL video (YouTube embed / MP4 / file upload)
            $table->text('video_url')->nullable();

            // Teks berjalan (running text)
            $table->text('running_text')->nullable();

            // Tambahan jika needed ke depan
            // $table->string('video_position')->default('top');  
            // $table->integer('video_height')->default(300);     

            $table->timestamps();
        });

        // Insert default kosong
        DB::table('monitor_settings')->insert([
            'video_url'     => null,
            'running_text'  => 'Selamat datang di layanan kami.',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitor_settings');
    }
}
