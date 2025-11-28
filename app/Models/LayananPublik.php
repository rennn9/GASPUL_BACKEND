<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LayananPublik extends Model
{
    protected $table = 'layanan_publik';

    protected $fillable = [
        'nik',
        'no_registrasi',
        'bidang',
        'layanan',
        'berkas'
    ];
}
