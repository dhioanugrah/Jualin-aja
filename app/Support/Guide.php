<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Daftar bab Buku Panduan (F14, Lampiran proposal). Isi tiap bab ada di
 * resources/views/guide/chapters/{slug}.blade.php.
 */
class Guide
{
    /**
     * @var array<string, array{title: string, icon: string, summary: string, admin?: bool}>
     */
    public const CHAPTERS = [
        'mulai' => ['title' => 'Mulai', 'icon' => 'sparkles', 'summary' => 'Daftar dengan email kampus, verifikasi, dan memilih Mode Pembeli atau Penjual.'],
        'pembeli' => ['title' => 'Untuk Pembeli', 'icon' => 'bag', 'summary' => 'Mencari barang, menawar, booking COD, kode serah terima, dan memberi rating.'],
        'penjual' => ['title' => 'Untuk Penjual', 'icon' => 'store', 'summary' => 'Memotret barang, mengisi form, moderasi, menerima booking, dan memasukkan kode.'],
        'cod-aman' => ['title' => 'COD Aman', 'icon' => 'shield-check', 'summary' => 'Titik Dalam dan Sekitar ITK, jam COD, dan kebiasaan aman saat bertemu.'],
        'aturan' => ['title' => 'Aturan & Sanksi', 'icon' => 'flag', 'summary' => 'Konfirmasi kehadiran, apa yang dihitung pelanggaran, dan cara membantah.'],
        'promosi' => ['title' => 'Iklan & Promosi', 'icon' => 'trending', 'summary' => 'Kuota promosi gratis, Bagikan barang, dan memasang banner iklan.'],
        'tanya-jawab' => ['title' => 'Tanya Jawab', 'icon' => 'message-circle', 'summary' => 'Lupa password, ganti nomor WA, barang ditolak moderasi, dan lainnya.'],
        'admin' => ['title' => 'Untuk Admin', 'icon' => 'settings', 'summary' => 'Moderasi, titik COD, iklan, laporan, sengketa, dan membaca dashboard.', 'admin' => true],
    ];

    /**
     * Bab yang boleh dilihat pengguna ini (bab Admin hanya untuk admin).
     *
     * @return Collection<string, array{title: string, icon: string, summary: string, admin?: bool}>
     */
    public static function chapters(bool $isAdmin = false): Collection
    {
        return collect(self::CHAPTERS)->reject(fn (array $chapter): bool => ($chapter['admin'] ?? false) && ! $isAdmin);
    }

    /**
     * Cari kata di judul dan isi bab. Mengembalikan potongan teks di sekitar kata yang cocok.
     *
     * @return Collection<string, array{title: string, icon: string, summary: string, excerpt: string}>
     */
    public static function search(string $term, bool $isAdmin = false): Collection
    {
        $needle = Str::lower(trim($term));

        if ($needle === '') {
            return collect();
        }

        return self::chapters($isAdmin)
            ->map(function (array $chapter, string $slug) use ($needle): ?array {
                $text = self::plainText($slug);
                $position = mb_strpos(Str::lower($text), $needle);

                if ($position === false) {
                    return str_contains(Str::lower($chapter['title']), $needle)
                        ? $chapter + ['excerpt' => $chapter['summary']]
                        : null;
                }

                $start = max(0, $position - 60);

                return $chapter + ['excerpt' => ($start > 0 ? '…' : '').trim(mb_substr($text, $start, 180)).'…'];
            })
            ->filter();
    }

    /**
     * Lokasi file PDF di disk "local" (F14): versi pengguna dan versi admin.
     */
    public static function pdfPath(bool $forAdmin = false): string
    {
        return $forAdmin ? 'guide/buku-panduan-admin.pdf' : 'guide/buku-panduan.pdf';
    }

    private static function plainText(string $slug): string
    {
        $html = view("guide.chapters.{$slug}")->render();

        return trim((string) preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($html))));
    }
}
