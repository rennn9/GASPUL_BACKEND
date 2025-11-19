<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Konsultasi extends Model
{
    use HasFactory;

    protected $table = 'konsultasi';

    protected $fillable = [
        'nama_lengkap',
        'no_hp_wa',          // 🔄 ubah dari no_hp
        'email',
        'alamat',            // 🆕 tambahan baru
        'perihal',
        'asal_instansi',
        'dokumen',
        'nomor_antrian',     // 🆕 tambahan baru
        'status',
        'tanggal_layanan',   // 🔄 ubah dari tanggal_konsultasi
    ];

    protected $casts = [
        'tanggal_layanan' => 'datetime',
    ];

    // 🔗 Relasi ke Antrian
    public function antrian()
    {
        return $this->hasOne(Antrian::class, 'konsultasi_id');
    }
}
