<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'nip' => '0000000001',
            'name' => 'Super Admin',
            'password' => Hash::make('password123'), // ✅ password
            'role' => 'superadmin',
        ]);

        User::create([
            'nip' => '0000000002',
            'name' => 'Admin Satu',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        User::create([
            'nip' => '0000000003',
            'name' => 'Operator Satu',
            'password' => Hash::make('password123'),
            'role' => 'operator',
        ]);
    }
}
