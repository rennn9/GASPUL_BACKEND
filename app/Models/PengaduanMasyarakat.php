<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengaduanMasyarakat extends Model
{
    use HasFactory;

    protected $table = 'pengaduan_masyarakat';

    protected $fillable = [
        'nama',
        'nip',
        'jenis_laporan',
        'penjelasan',
        'file_path',
    ];

}
