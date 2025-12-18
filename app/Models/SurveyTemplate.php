<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyTemplate extends Model
{
    protected $fillable = [
        'nama',
        'deskripsi',
        'versi',
        'is_active',
        'created_by_user_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'versi' => 'integer',
    ];

    /**
     * Relasi ke User (creator)
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Relasi ke SurveyQuestion (one to many)
     */
    public function questions()
    {
        return $this->hasMany(SurveyQuestion::class, 'survey_template_id')->orderBy('urutan', 'asc');
    }

    /**
     * Relasi ke Survey (one to many)
     */
    public function surveys()
    {
        return $this->hasMany(Survey::class, 'survey_template_id');
    }

    /**
     * Scope untuk template aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get active template
     */
    public static function getActive()
    {
        return static::where('is_active', true)->with('questions.options')->first();
    }
}
