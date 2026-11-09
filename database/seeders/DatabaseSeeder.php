<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Data demo Sprint 1–3. Semua akun memakai password "password".
     */
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            CodLocationSeeder::class,
            UserSeeder::class,
            ProductSeeder::class,
            TransactionSeeder::class,
            CommunitySeeder::class,
        ]);
    }
}
