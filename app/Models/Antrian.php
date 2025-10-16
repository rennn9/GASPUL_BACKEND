<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Antrian extends Model
{
    use HasFactory;

    protected $table = 'antrian';

    protected $fillable = [
        'nama',
        'no_hp',
        'alamat',
        'bidang_layanan',
        'layanan',
        'tanggal_daftar',
        'keterangan',
        'nomor_antrian',
        'qr_code_data',
        'status',
    ];
}
