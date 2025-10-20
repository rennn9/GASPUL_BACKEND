<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Konsultasi extends Model
{
    protected $fillable = [
        'nama_lengkap',
        'no_hp',
        'email',
        'perihal',
        'isi_konsultasi',
        'dokumen',
        'status',
        'tanggal_konsultasi'
    ];

    protected $casts = [
        'tanggal_konsultasi' => 'datetime',
    ];
}
