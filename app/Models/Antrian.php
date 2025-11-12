<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Antrian extends Model
{
    use HasFactory;

    protected $table = 'antrian';

    protected $fillable = [
        'konsultasi_id',
        'nama_lengkap',
        'no_hp_wa',
        'email',
        'alamat',
        'bidang_layanan',
        'layanan',
        'tanggal_layanan',
        'keterangan',
        'nomor_antrian',
        'qr_code_data',
        'status',
    ];

    protected $casts = [
        // Gunakan format tanggal tetap, tanpa jam dan zona waktu
        'tanggal_layanan' => 'datetime:Y-m-d',
    ];

    // 🔗 Relasi ke Konsultasi
    public function konsultasi()
    {
        return $this->belongsTo(Konsultasi::class, 'konsultasi_id');
    }

    // ✅ Pastikan setiap kali tanggal_layanan diambil, tetap di zona Asia/Makassar
    public function getTanggalLayananAttribute($value)
    {
        return Carbon::parse($value)
            ->timezone('Asia/Makassar')
            ->format('Y-m-d');
    }

    public function survey()
    {
        return $this->hasOne(\App\Models\Survey::class, 'nomor_antrian', 'nomor_antrian');
    }

}
