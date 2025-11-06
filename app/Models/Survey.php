<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Survey extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_responden',
        'nomor_whatsapp',
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
}
