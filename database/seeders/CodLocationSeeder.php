<?php

namespace Database\Seeders;

use App\Enums\CodArea;
use App\Models\CodLocation;
use Illuminate\Database\Seeder;

class CodLocationSeeder extends Seeder
{
    /**
     * Titik COD contoh. Perlu diganti dengan titik nyata di dan sekitar kampus ITK
     * (admin bisa mengubahnya lewat panel).
     */
    public function run(): void
    {
        $locations = [
            ['Lobi Gedung Kuliah Bersama', CodArea::Inside],
            ['Perpustakaan ITK', CodArea::Inside],
            ['Masjid Kampus ITK', CodArea::Inside],
            ['Gerbang Utama ITK', CodArea::Inside],
            ['Asrama Mahasiswa ITK', CodArea::Inside],
            ['Minimarket dekat gerbang ITK (contoh)', CodArea::Around],
            ['Kafe sekitar Karang Joang (contoh)', CodArea::Around],
            ['Masjid sekitar Karang Joang (contoh)', CodArea::Around],
        ];

        foreach ($locations as [$name, $area]) {
            CodLocation::query()->updateOrCreate(['name' => $name], [
                'area' => $area,
                'maps_url' => 'https://www.google.com/maps/search/?api=1&query='.urlencode($name.' Balikpapan'),
                'is_active' => true,
            ]);
        }
    }
}
