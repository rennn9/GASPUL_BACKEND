<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Survey extends Model
{
    use HasFactory;

    protected $fillable = [
        'survey_template_id',
        'antrian_id',
        'nomor_antrian',
        'layanan_publik_id',
        'nama_responden',
        'no_hp_wa',
        'usia',
        'jenis_kelamin',
        'pendidikan',
        'pekerjaan',
        'bidang',
        'tanggal',
        'jawaban',
        'saran',
        'surveyed_at',
    ];

    protected $casts = [
        'jawaban' => 'array',
        'tanggal' => 'date',
        'surveyed_at' => 'datetime',
    ];

    public function antrian()
    {
        return $this->belongsTo(Antrian::class, 'antrian_id');
    }

    public function layananPublik()
    {
        return $this->belongsTo(LayananPublik::class, 'layanan_publik_id');
    }

    /**
     * Relasi ke SurveyTemplate (belongs to)
     */
    public function template()
    {
        return $this->belongsTo(SurveyTemplate::class, 'survey_template_id');
    }

    /**
     * Relasi ke SurveyResponse (one to many)
     */
    public function responses()
    {
        return $this->hasMany(SurveyResponse::class, 'survey_id');
    }

}
