<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * 7 kategori utama (jelajah per jenis).
     */
    public function run(): void
    {
        $categories = [
            'Buku & Modul' => 'book-open',
            'Elektronik' => 'laptop',
            'Alat Praktikum' => 'flask',
            'Perabot Kos' => 'sofa',
            'Fashion' => 'shirt',
            'Kendaraan & Aksesori' => 'bike',
            'Lainnya' => 'package',
        ];

        foreach ($categories as $name => $icon) {
            Category::query()->updateOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'icon' => $icon]);
        }
    }
}
