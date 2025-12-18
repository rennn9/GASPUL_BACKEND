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
        Schema::create('survey_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('survey_template_id');
            $table->text('pertanyaan_text');
            $table->string('kode_unsur', 10)->nullable()->comment('U1, U2, U3... untuk IKM');
            $table->integer('urutan');
            $table->boolean('is_required')->default(true);
            $table->boolean('is_text_input')->default(false)->comment('True untuk pertanyaan terbuka');
            $table->timestamps();

            $table->foreign('survey_template_id')->references('id')->on('survey_templates')->onDelete('cascade');
            $table->index(['survey_template_id', 'urutan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_questions');
    }
};
