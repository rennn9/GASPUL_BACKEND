<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Antrian extends Model
{
    use HasFactory;

    protected $table = 'antrian';

    protected $fillable = [
        'konsultasi_id',   // 🔹 tambahkan ini untuk relasi
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

    protected $casts = [
        'tanggal_daftar' => 'date',
    ];

    // 🔹 Relasi ke model Konsultasi
    public function konsultasi()
    {
        return $this->belongsTo(\App\Models\Konsultasi::class, 'konsultasi_id');
    }
}
