<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Buat Super Admin
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@admin.com',
            'password' => Hash::make('123'), // Password default
            'role' => 'super_admin',
        ]);

        // 2. Buat Dummy Dosen (Untuk Test)
        User::create([
            'name' => 'Admin kelas',
            'email' => 'adminkelas@admin.com',
            'password' => Hash::make('123'),
            'role' => 'admin_kelas',
            'phone' => '08123456789',
        ]);

        // 3. Buat Dummy Mahasiswa
        User::create([
            'name' => 'Andi',
            'email' => 'andi@gmail.com',
            'password' => Hash::make('123'),
            'role' => 'mahasiswa',
            'nim' => '10120202',
        ]);
    }
}
