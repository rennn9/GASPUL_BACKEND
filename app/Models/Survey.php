<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Survey extends Model
{
    use HasFactory;

    protected $fillable = [
        'nomor_antrian',
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
    ];

    protected $casts = [
        'jawaban' => 'array',
        'tanggal' => 'date',
    ];

    public function antrian()
    {
        return $this->belongsTo(Antrian::class, 'antrian_id');
    }

}
