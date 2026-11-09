<?php

namespace Database\Seeders;

use App\Enums\UserMode;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * 1 admin dan 6 mahasiswa demo. Semua password: "password".
     */
    public function run(): void
    {
        $domain = config('jualin.email_domain');

        User::factory()->create([
            'name' => 'Admin Jualin',
            'nim' => null,
            'email' => 'admin@jualinaja.test',
            'whatsapp' => null,
            'role' => UserRole::Admin,
        ]);

        $students = [
            ['Aulia Rahmawati', '11221001', 'Informatika', 2022, UserMode::Seller],
            ['Bagas Pratama', '11211014', 'Teknik Mesin', 2021, UserMode::Seller],
            ['Nadya Kirana', '11231022', 'Teknik Kimia', 2023, UserMode::Seller],
            ['Dimas Saputra', '11201007', 'Teknik Sipil', 2020, UserMode::Seller],
            ['Intan Permata', '11241031', 'Matematika', 2024, UserMode::Buyer],
            ['Fajar Hidayat', '11241045', 'Sistem Informasi', 2024, UserMode::Buyer],
        ];

        foreach ($students as [$name, $nim, $prodi, $angkatan, $mode]) {
            User::factory()->create([
                'name' => $name,
                'nim' => $nim,
                'email' => $nim.'@'.$domain,
                'prodi' => $prodi,
                'angkatan' => $angkatan,
                'active_mode' => $mode,
            ]);
        }
    }
}
