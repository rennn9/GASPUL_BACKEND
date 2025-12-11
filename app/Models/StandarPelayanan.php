<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StandarPelayanan extends Model
{
    protected $table = 'standar_pelayanan';

    protected $fillable = [
        'bidang',
        'layanan',
        'file_path',
    ];

    /**
     * Return full public URL to the file (if exists).
     */
    public function getFileUrlAttribute()
    {
        if (! $this->file_path) return null;
        return asset('storage/' . ltrim($this->file_path, '/'));
    }
}
