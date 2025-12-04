<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'nip',
        'name',
        'password',
        'role',
        'bidang', // kolom baru
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // Gunakan NIP untuk login
    public function getAuthIdentifierName()
    {
        return 'nip';
    }
}
