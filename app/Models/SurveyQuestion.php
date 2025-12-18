<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyQuestion extends Model
{
    protected $fillable = [
        'survey_template_id',
        'pertanyaan_text',
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
