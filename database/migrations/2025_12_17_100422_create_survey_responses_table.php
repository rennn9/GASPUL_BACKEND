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
        Schema::create('survey_responses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('survey_id');
            $table->unsignedBigInteger('survey_question_id');
            $table->unsignedBigInteger('survey_option_id')->nullable()->comment('NULL jika text input');
            $table->string('jawaban_text')->nullable()->comment('Snapshot jawaban');
            $table->integer('poin')->nullable()->comment('Snapshot poin');
            $table->timestamps();

            $table->foreign('survey_id')->references('id')->on('surveys')->onDelete('cascade');
            $table->foreign('survey_question_id')->references('id')->on('survey_questions')->onDelete('restrict');
            $table->foreign('survey_option_id')->references('id')->on('survey_question_options')->onDelete('restrict');
            $table->index('survey_id');
            $table->index('survey_question_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_responses');
    }
};
