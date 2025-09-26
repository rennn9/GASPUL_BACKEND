<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengaduanPelayanan extends Model
{
    use HasFactory;

    protected $table = 'pengaduan_pelayanan';

    protected $fillable = [
        'nama',
        'nip',
        'penjelasan',
        'file_path',
    ];

}
