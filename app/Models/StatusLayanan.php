<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusLayanan extends Model
{
    protected $table = 'status_layanan';

    protected $fillable = [
        'layanan_id',
        'user_id',
        'status',
        'keterangan',
        'file_surat',
        'file_perbaikan',
        'operator_nama',
        'operator_no_hp',
    ];

    protected $casts = [
        'file_surat' => 'array', // karena kita simpan file surat sebagai JSON array
    ];

    // Relasi ke LayananPublik
    public function layanan()
    {
        return $this->belongsTo(LayananPublik::class, 'layanan_id');
    }

    // Relasi ke User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
