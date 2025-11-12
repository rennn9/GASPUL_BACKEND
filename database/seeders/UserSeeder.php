<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Jalankan seeder user default.
     */
    public function run(): void
    {
        User::create([
            'nip' => '12345678',          // NIP unik untuk login
            'name' => 'Administrator',    // Nama user
            'password' => Hash::make('password123'), // Password terenkripsi (Bcrypt)
            'role' => 'superadmin',            // Role sesuai kebutuhan kamu
        ]);
    }
}
