<?php

namespace Database\Seeders;

use App\Enums\CodArea;
use App\Enums\HandoverMethod;
use App\Enums\ProductCondition;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Gradien foto contoh per kategori.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const PALETTES = [
        'buku-modul' => ['#C7D7EE', '#8BB1E3'],
        'elektronik' => ['#D9E2EC', '#94A3B8'],
        'alat-praktikum' => ['#CFF3E4', '#6CCBA3'],
        'perabot-kos' => ['#FBE7C6', '#F2C94C'],
        'fashion' => ['#F9D7E1', '#E68AA6'],
        'kendaraan-aksesori' => ['#DDD6FE', '#A78BFA'],
        'lainnya' => ['#E2E8F0', '#CBD5E1'],
    ];

    /**
     * Ikon garis per kategori untuk foto contoh.
     *
     * @var array<string, string>
     */
    private const ICONS = [
        'buku-modul' => '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>',
        'elektronik' => '<rect width="18" height="12" x="3" y="4" rx="2"/><path d="M2 20h20"/>',
        'alat-praktikum' => '<path d="M10 2v7.31"/><path d="M14 9.3V2"/><path d="M8.5 2h7"/><path d="M14 9.3a6.5 6.5 0 1 1-4 0"/><path d="M5.52 16h12.96"/>',
        'perabot-kos' => '<path d="M20 9V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v3"/><path d="M2 11v5a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-5a2 2 0 0 0-4 0v2H6v-2a2 2 0 0 0-4 0Z"/>',
        'fashion' => '<path d="M20.38 3.46 16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.47a1 1 0 0 0 .99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 0 0 2-2V10h2.15a1 1 0 0 0 .99-.84l.58-3.47a2 2 0 0 0-1.34-2.23z"/>',
        'kendaraan-aksesori' => '<circle cx="18.5" cy="17.5" r="3.5"/><circle cx="5.5" cy="17.5" r="3.5"/><circle cx="15" cy="5" r="1"/><path d="M12 17.5V14l-3-3 4-3 2 3h2"/>',
        'lainnya' => '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="M3.29 7 12 12l8.71-5"/><path d="M12 22V12"/>',
    ];

    /**
     * Sekitar 40 barang demo dengan berbagai status.
     */
    public function run(): void
    {
        $sellers = User::query()->whereIn('nim', ['11221001', '11211014', '11231022', '11201007'])->get()->keyBy('nim');
        $categories = Category::query()->get()->keyBy('slug');
        $inside = [CodArea::Inside];
        $both = [CodArea::Inside, CodArea::Around];

        // [nim penjual, slug kategori, nama, harga, kondisi, status, area COD, deskripsi]
        $items = [
            ['11221001', 'buku-modul', 'Kalkulus Purcell Jilid 1 Edisi 9', 45000, 'good', 'approved', $inside, 'Dipakai satu semester untuk Kalkulus 1. Ada stabilo di beberapa bab, halaman lengkap.'],
            ['11221001', 'buku-modul', 'Fisika Dasar Halliday Jilid 2', 60000, 'like_new', 'approved', $both, 'Masih mulus, sampul plastik. Cocok untuk Fisika Dasar 2.'],
            ['11221001', 'buku-modul', 'Modul Praktikum Algoritma & Pemrograman', 15000, 'good', 'approved', $inside, 'Modul cetak lengkap dengan catatan jawaban di pinggir halaman.'],
            ['11221001', 'elektronik', 'Mouse Wireless Logitech M185', 75000, 'like_new', 'approved', $both, 'Baru dipakai 3 bulan, receiver lengkap. Baterai baru diganti.'],
            ['11221001', 'elektronik', 'Kalkulator Casio fx-991ID Plus', 120000, 'good', 'approved', $inside, 'Boleh dipakai untuk ujian. Ada goresan kecil di tutup.'],
            ['11221001', 'perabot-kos', 'Rak Buku Lipat 3 Susun', 55000, 'good', 'approved', $inside, 'Rak plastik lipat, kuat untuk buku kuliah. Ambil di asrama.'],
            ['11221001', 'buku-modul', 'Buku Statistika Walpole', 50000, 'fair', 'needs_revision', $inside, 'Buku statistika untuk mata kuliah Probstat.'],
            ['11221001', 'elektronik', 'Flashdisk Sandisk 32GB', 40000, 'like_new', 'pending', $inside, 'Kecepatan baca masih normal, sudah diformat.'],
            ['11221001', 'buku-modul', 'Kimia Dasar Chang Jilid 1', 55000, 'good', 'sold', $inside, 'Sudah terjual ke adik tingkat.'],

            ['11211014', 'kendaraan-aksesori', 'Helm Bogo Retro Ukuran L', 85000, 'good', 'approved', $both, 'Kaca masih jernih, busa dalam sudah dicuci.'],
            ['11211014', 'kendaraan-aksesori', 'Sepeda Lipat Element 20 inci', 850000, 'good', 'approved', $both, 'Rem depan-belakang pakem, ban baru ganti bulan lalu. Cocok untuk keliling kampus.'],
            ['11211014', 'alat-praktikum', 'Jangka Sorong Digital Tricle 150mm', 95000, 'like_new', 'approved', $inside, 'Untuk praktikum Metrologi. Baterai cadangan ikut.'],
            ['11211014', 'alat-praktikum', 'Set Pensil Mekanik Gambar Teknik', 35000, 'good', 'approved', $inside, 'Isi 0.3, 0.5, dan 0.7. Pas untuk Menggambar Teknik.'],
            ['11211014', 'buku-modul', 'Mekanika Teknik Hibbeler Statika', 70000, 'good', 'approved', $inside, 'Versi bahasa Indonesia, lengkap dengan soal latihan.'],
            ['11211014', 'elektronik', 'Kipas Angin Meja Miyako 12 inci', 80000, 'good', 'approved', $both, 'Tiga kecepatan normal semua. Penyelamat kos yang panas.'],
            ['11211014', 'perabot-kos', 'Meja Lipat Laptop Kayu', 45000, 'good', 'approved', $inside, 'Bisa dipakai di kasur. Ada sedikit lecet di kaki meja.'],
            ['11211014', 'fashion', 'Wearpack Teknik Ukuran L', 90000, 'good', 'pending', $inside, 'Wearpack biru dongker, dipakai untuk praktikum bengkel.'],
            ['11211014', 'kendaraan-aksesori', 'Jas Hujan Ponco Axio', 30000, 'like_new', 'sold', $inside, 'Sudah terjual.'],

            ['11231022', 'alat-praktikum', 'Jas Lab Putih Ukuran M', 0, 'good', 'approved', $inside, 'Didonasikan untuk adik tingkat Teknik Kimia. Sudah dicuci bersih.'],
            ['11231022', 'alat-praktikum', 'Kacamata Safety Lab', 20000, 'like_new', 'approved', $inside, 'Dipakai dua kali praktikum, masih bening.'],
            ['11231022', 'buku-modul', 'Modul Kimia Organik 1', 20000, 'good', 'approved', $inside, 'Modul cetak dari himpunan, ada rangkuman tulisan tangan.'],
            ['11231022', 'perabot-kos', 'Rice Cooker Mini Cosmos 0.6L', 90000, 'good', 'approved', $both, 'Pas untuk satu orang. Penghangat masih berfungsi.'],
            ['11231022', 'perabot-kos', 'Lampu Belajar LED Lipat', 45000, 'like_new', 'approved', $both, 'Tiga mode cahaya, bisa dicas lewat USB.'],
            ['11231022', 'fashion', 'Almamater ITK Ukuran S', 100000, 'good', 'approved', $inside, 'Almamater ukuran S, jahitan rapi, tidak ada noda.'],
            ['11231022', 'fashion', 'Sepatu Pantofel Wanita 38', 75000, 'good', 'approved', $inside, 'Dipakai untuk sidang dan seminar, sol masih tebal.'],
            ['11231022', 'lainnya', 'Galon Air + Pompa Elektrik', 60000, 'good', 'approved', $both, 'Pompa bisa dicas, galon bersih.'],
            ['11231022', 'elektronik', 'Headset Gaming Rexus', 110000, 'fair', 'needs_revision', $both, 'Headset gaming suara jernih.'],
            ['11231022', 'lainnya', 'Tumbler Stainless 750ml', 35000, 'like_new', 'pending', $inside, 'Tahan panas dan dingin sampai 8 jam.'],

            ['11201007', 'buku-modul', 'Paket Buku Teknik Sipil Semester 3', 150000, 'good', 'approved', $both, 'Isi 4 buku: Mekanika Tanah, Hidrologi, Ilmu Ukur Tanah, dan Beton. Lebih hemat beli paket.'],
            ['11201007', 'alat-praktikum', 'Meteran Gulung 50m', 65000, 'good', 'approved', $both, 'Untuk praktikum Ilmu Ukur Tanah. Gulungan lancar.'],
            ['11201007', 'elektronik', 'Monitor LG 22 inci Full HD', 750000, 'good', 'approved', $both, 'Tidak ada dead pixel. Kabel HDMI ikut.'],
            ['11201007', 'elektronik', 'Printer Canon iP2770', 350000, 'fair', 'approved', $both, 'Masih bisa cetak hitam dan warna. Tinta perlu diisi ulang.'],
            ['11201007', 'perabot-kos', 'Kasur Lipat Busa 90x200', 200000, 'good', 'approved', $both, 'Busa masih empuk, sarung bisa dilepas dan dicuci.'],
            ['11201007', 'perabot-kos', 'Dispenser Miyako Hot & Normal', 120000, 'good', 'approved', $both, 'Pemanas berfungsi baik. Ambil di kos dekat ITK.'],
            ['11201007', 'kendaraan-aksesori', 'Kunci Cakram Motor', 40000, 'like_new', 'approved', $inside, 'Lengkap dengan dua kunci dan kantong.'],
            ['11201007', 'lainnya', 'Tas Ransel Eiger 25L', 160000, 'good', 'approved', $both, 'Ada slot laptop 14 inci. Resleting lancar semua.'],
            ['11201007', 'fashion', 'Kemeja Hitam Putih Ospek', 0, 'good', 'approved', $inside, 'Donasi untuk mahasiswa baru. Ukuran M.'],
            ['11201007', 'elektronik', 'Laptop Asus Vivobook 14 i3', 3200000, 'good', 'needs_revision', $both, 'Laptop untuk kuliah.'],
            ['11201007', 'buku-modul', 'Struktur Beton Bertulang SNI', 65000, 'good', 'pending', $inside, 'Buku referensi beton bertulang berdasarkan SNI terbaru.'],
            ['11201007', 'perabot-kos', 'Lemari Plastik 4 Susun', 150000, 'good', 'sold', $both, 'Sudah terjual.'],
        ];

        $revisionNotes = [
            'Foto kurang jelas dan hanya satu sudut. Tambahkan foto sampul dan halaman dalam.',
            'Deskripsi terlalu singkat. Sebutkan kondisi bantalan telinga dan kabel.',
            'Sebutkan spesifikasi (RAM, SSD), kondisi baterai, dan sertakan foto layar menyala.',
        ];

        Storage::disk('public')->deleteDirectory('products/seed');

        foreach ($items as $index => [$nim, $categorySlug, $name, $price, $condition, $status, $areas, $description]) {
            $status = ProductStatus::from($status);

            $product = new Product([
                'category_id' => $categories[$categorySlug]->id,
                'name' => $name,
                'description' => $description,
                'condition' => ProductCondition::from($condition),
                'price' => $price,
                'is_donation' => $price === 0,
                'handover_method' => HandoverMethod::Cod,
                'cod_areas' => $areas,
                'location' => fake()->randomElement(['Asrama ITK', 'Kos Karang Joang', 'Gedung Kuliah Bersama', null]),
            ]);
            $product->user_id = $sellers[$nim]->id;
            $product->status = $status;
            $product->moderation_note = $status === ProductStatus::NeedsRevision ? array_shift($revisionNotes) : null;
            $product->sold_at = $status === ProductStatus::Sold ? now()->subDays(random_int(1, 20)) : null;
            $product->views = $status === ProductStatus::Approved ? random_int(5, 180) : 0;
            $product->created_at = now()->subHours(($index * 7) + random_int(0, 6));
            $product->updated_at = $product->created_at;
            $product->save();

            $photoCount = $status === ProductStatus::NeedsRevision ? 1 : random_int(1, 3);

            for ($photo = 0; $photo < $photoCount; $photo++) {
                $path = 'products/seed/'.$product->slug.'-'.$photo.'.svg';
                Storage::disk('public')->put($path, $this->placeholderSvg($categorySlug, $name, $photo));
                $product->images()->create(['path' => $path, 'order' => $photo]);
            }
        }
    }

    /**
     * Foto contoh berupa SVG (gradien + ikon kategori) agar seeder tidak butuh ekstensi GD.
     */
    private function placeholderSvg(string $categorySlug, string $name, int $variant): string
    {
        [$from, $to] = self::PALETTES[$categorySlug];
        $icon = self::ICONS[$categorySlug];
        $label = e(Str::limit($name, 34));
        $angle = [135, 45, 200][$variant % 3];
        $id = 'g'.Str::random(6);

        return <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 600">
          <defs><linearGradient id="{$id}" gradientTransform="rotate({$angle} .5 .5)"><stop offset="0" stop-color="{$from}"/><stop offset="1" stop-color="{$to}"/></linearGradient></defs>
          <rect width="800" height="600" fill="url(#{$id})"/>
          <circle cx="690" cy="90" r="150" fill="#fff" opacity=".18"/>
          <circle cx="90" cy="560" r="190" fill="#fff" opacity=".12"/>
          <g transform="translate(310 170) scale(7.5)" fill="none" stroke="#fff" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">{$icon}</g>
          <text x="400" y="520" text-anchor="middle" font-family="Plus Jakarta Sans, Segoe UI, sans-serif" font-size="30" font-weight="700" fill="#fff" opacity=".95">{$label}</text>
        </svg>
        SVG;
    }
}
