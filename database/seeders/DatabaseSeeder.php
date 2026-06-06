<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $password = 'rahasia';

        User::create([
            'name'       => 'Narendra',
            'slug'       => '@narendra.dev',
            'email'      => 'narendra@janganlupa.dev',
            'password'   => Hash::make($password),
            'role_label' => 'Fullstack Developer',
            'timezone'   => 'Asia/Jakarta',
            'status'     => 'Aktif',
            'focus'      => 'Menyelesaikan PBL Semester 2',
        ]);

        User::create([
            'name'       => 'Ardhiva Putra',
            'slug'       => '@ardhiva',
            'email'      => 'ardhiva@janganlupa.dev',
            'password'   => Hash::make($password),
            'role_label' => 'UI/UX Designer',
            'timezone'   => 'Asia/Jakarta',
            'status'     => 'Aktif',
            'focus'      => 'Desain antarmuka pengguna',
        ]);

        User::create([
            'name'       => 'Sinta Dewi',
            'slug'       => '@sinta.dewi',
            'email'      => 'sinta@janganlupa.dev',
            'password'   => Hash::make($password),
            'role_label' => 'Frontend Developer',
            'timezone'   => 'Asia/Jakarta',
            'status'     => 'Aktif',
            'focus'      => 'Integrasi API dan komponen React',
        ]);

        User::create([
            'name'       => 'Budi Santoso',
            'slug'       => '@budi.s',
            'email'      => 'budi@janganlupa.dev',
            'password'   => Hash::make($password),
            'role_label' => 'Backend Developer',
            'timezone'   => 'Asia/Jakarta',
            'status'     => 'Aktif',
            'focus'      => 'Optimalisasi performa server',
        ]);
    }
}
