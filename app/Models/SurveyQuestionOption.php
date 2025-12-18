<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyQuestionOption extends Model
{
    protected $fillable = [
        'survey_question_id',
        'jawaban_text',
        'poin',
        'urutan',
    ];

    protected $casts = [
        'survey_question_id' => 'integer',
        'poin' => 'integer',
        'urutan' => 'integer',
    ];

    /**
     * Relasi ke SurveyQuestion (belongs to)
     */
    public function question()
    {
        return $this->belongsTo(SurveyQuestion::class, 'survey_question_id');
    }

    /**
     * Relasi ke SurveyResponse (one to many)
     */
    public function responses()
    {
        return $this->hasMany(SurveyResponse::class, 'survey_option_id');
    }
}
