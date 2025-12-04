<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LayananPublik extends Model
{
    protected $table = 'layanan_publik';

    protected $fillable = [
        'nik',
        'nama',
        'email',
        'telepon',
        'no_registrasi',
        'bidang',
        'layanan',
        'berkas'
    ];

    /**
     * Relasi ke status_layanan (history status)
     * Mengurutkan dari status pertama sampai terakhir
     */
    public function statusHistory()
    {
        return $this->hasMany(StatusLayanan::class, 'layanan_id')
                    ->orderBy('created_at', 'asc');
    }

    /**
     * Ambil status terakhir dari entri ini
     */
    public function lastStatus()
    {
        return $this->hasOne(StatusLayanan::class, 'layanan_id')
                    ->latest('created_at');
    }
}
