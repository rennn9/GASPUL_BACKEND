<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyQuestion extends Model
{
    protected $fillable = [
        'survey_template_id',
        'pertanyaan_text',
        'unsur_pelayanan',
        'kode_unsur',
        'urutan',
        'is_required',
        'is_text_input',
    ];

    protected $casts = [
        'survey_template_id' => 'integer',
        'urutan' => 'integer',
        'is_required' => 'boolean',
        'is_text_input' => 'boolean',
    ];

    protected $appends = [
        'pertanyaan',
        'tipe_jawaban',
    ];

    /**
     * Accessor untuk field 'pertanyaan' (alias dari pertanyaan_text)
     * Untuk kompatibilitas dengan Flutter app
     */
    public function getPertanyaanAttribute()
    {
        return $this->pertanyaan_text;
    }

    /**
     * Accessor untuk field 'tipe_jawaban'
     * Mapping dari is_text_input ke format yang diharapkan Flutter
     */
    public function getTipeJawabanAttribute()
    {
        if ($this->is_text_input) {
            return 'text';
        }
        return 'pilihan_ganda';
    }

    /**
     * Relasi ke SurveyTemplate (belongs to)
     */
    public function template()
    {
        return $this->belongsTo(SurveyTemplate::class, 'survey_template_id');
    }

    /**
     * Relasi ke SurveyQuestionOption (one to many)
     */
    public function options()
    {
        return $this->hasMany(SurveyQuestionOption::class, 'survey_question_id')->orderBy('urutan', 'asc');
    }

    /**
     * Relasi ke SurveyResponse (one to many)
     */
    public function responses()
    {
        return $this->hasMany(SurveyResponse::class, 'survey_question_id');
    }
}
