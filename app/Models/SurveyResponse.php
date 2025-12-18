<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyResponse extends Model
{
    protected $fillable = [
        'survey_id',
        'survey_question_id',
        'survey_option_id',
        'jawaban_text',
        'poin',
    ];

    protected $casts = [
        'survey_id' => 'integer',
        'survey_question_id' => 'integer',
        'survey_option_id' => 'integer',
        'poin' => 'integer',
    ];

    /**
     * Relasi ke Survey (belongs to)
     */
    public function survey()
    {
        return $this->belongsTo(Survey::class, 'survey_id');
    }

    /**
     * Relasi ke SurveyQuestion (belongs to)
     */
    public function question()
    {
        return $this->belongsTo(SurveyQuestion::class, 'survey_question_id');
    }

    /**
     * Relasi ke SurveyQuestionOption (belongs to)
     */
    public function option()
    {
        return $this->belongsTo(SurveyQuestionOption::class, 'survey_option_id');
    }
}
