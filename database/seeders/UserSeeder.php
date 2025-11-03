<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::create([
            'name' => 'Admin Kelurahan',
            'email' => 'admin@laporpak.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'address' => 'Kantor Kelurahan',
            'phone' => '081234567890',
        ]);

        // Petugas
        User::create([
            'name' => 'Pak Udin',
            'email' => 'udin@laporpak.com',
            'password' => Hash::make('password'),
            'role' => 'petugas',
            'address' => 'Kelurahan',
            'phone' => '081234567891',
        ]);

        // RT (Rukun Tetangga)
        User::create([
            'name' => 'Pak RT',
            'email' => 'rt@laporpak.com',
            'password' => Hash::make('password'),
            'role' => 'rt',
            'address' => 'Ketua RT 05',
            'phone' => '081234567899',
        ]);

        // Warga
        User::create([
            'name' => 'Kentangtintung',
            'email' => 'adaktadabru@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'warga',
            'address' => 'Perumahan Alfa, Blok A-15, Blimbing, Kec. Torimjao, Kota Malang, 65112',
            'phone' => '081234567892',
        ]);

        // More warga
        User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'warga',
            'address' => 'Jl. Merdeka No. 123',
            'phone' => '081234567893',
        ]);

        User::create([
            'name' => 'Siti Aminah',
            'email' => 'siti@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'warga',
            'address' => 'Jl. Sudirman No. 456',
            'phone' => '081234567894',
        ]);
    }
}