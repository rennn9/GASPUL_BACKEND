<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Konsultasi extends Model
{
    // Specify table name explicitly (Laravel default would be 'konsultasis')
    protected $table = 'konsultasi';

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
