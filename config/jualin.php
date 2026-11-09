<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Domain email kampus
    |--------------------------------------------------------------------------
    |
    | Hanya email dengan domain ini yang bisa mendaftar. Perlu dicek ulang
    | dengan domain email mahasiswa ITK yang sebenarnya.
    |
    */

    'email_domain' => env('JUALIN_EMAIL_DOMAIN', 'student.itk.ac.id'),

    /*
    |--------------------------------------------------------------------------
    | Foto barang
    |--------------------------------------------------------------------------
    */

    'max_photos' => 5,

    'max_photo_kb' => 2048,

    /*
    |--------------------------------------------------------------------------
    | Kontak admin untuk pertanyaan dan pemasangan iklan
    |--------------------------------------------------------------------------
    */

    'admin_email' => env('JUALIN_ADMIN_EMAIL', 'admin@jualinaja.test'),

    /*
    |--------------------------------------------------------------------------
    | Aturan transaksi aman (Proposal Revisi 2, bab 3.3 dan 3.4)
    |--------------------------------------------------------------------------
    |
    | Angka-angka ini usulan awal dan dievaluasi saat uji pengguna.
    |
    */

    'catalog_per_page' => 24,

    // Batas kontak WA ke barang baru per akun per hari.
    'contact_daily_limit' => 15,

    // Penawaran yang diterima harus dijadikan booking dalam sekian jam.
    'offer_booking_window_hours' => 24,

    // Pembeli maksimal punya sekian booking aktif (Menunggu + Diterima).
    'max_active_bookings' => 2,

    // Penjual harus merespons booking dalam sekian jam.
    'booking_respond_hours' => 12,

    // Waktu COD paling cepat sekian jam dari sekarang, dan hanya di jam berikut (WITA).
    'cod_min_lead_hours' => 1,
    'cod_open_hour' => 7,
    'cod_close_hour' => 21,

    // Konfirmasi kehadiran dibuka sekian jam sebelum COD, dan batas akhirnya sekian jam sebelum COD.
    'confirm_opens_hours' => 3,
    'confirm_deadline_hours' => 1,

    // Batal kurang dari sekian jam sebelum COD dihitung pelanggaran "batal mendadak".
    'late_cancel_hours' => 3,

    // Pihak yang dilaporkan "tidak datang" bisa membantah dalam sekian jam.
    'dispute_hours' => 24,

    // Kode serah terima: batas salah input per booking per 15 menit.
    'handover_max_attempts' => 5,

    // Sanksi berdasarkan jumlah pelanggaran aktif dalam 30 hari terakhir.
    'violation_window_days' => 30,
    'sanctions' => [
        2 => 3,   // 2 pelanggaran: tidak bisa booking 3 hari
        3 => 14,  // 3 pelanggaran: tidak bisa booking 14 hari dan ditinjau admin
    ],

    // Skor keandalan baru ditampilkan setelah sekian transaksi; sebelumnya "baru".
    'reliability_min_bookings' => 1,

    /*
    |--------------------------------------------------------------------------
    | Reputasi, promosi, dan iklan (Sprint 3: F11, F15)
    |--------------------------------------------------------------------------
    */

    // Ulasan bisa diberikan sampai sekian hari setelah booking Selesai.
    'review_window_days' => 14,

    // Tag ulasan per arah.
    'review_tags' => [
        'buyer_to_seller' => ['Ramah', 'Barang Sesuai', 'Tepat Waktu', 'Responsif', 'Harga Pas'],
        'seller_to_buyer' => ['Ramah', 'Tepat Waktu', 'Responsif', 'Pembayaran Pas'],
    ],

    // Lencana "Penjual Terbaik": rata-rata minimal dari minimal sekian ulasan.
    'top_seller_min_rating' => 4.5,
    'top_seller_min_reviews' => 5,

    // Promosi barang gratis: lama tayang, kuota per penjual per 7 hari, dan slot yang tampil bergiliran.
    'promotion_days' => 3,
    'promotion_weekly_quota' => 1,
    'promotion_slots' => 8,

    // Banner iklan: gambar maksimal (KB).
    'ad_max_image_kb' => 2048,

    /*
    |--------------------------------------------------------------------------
    | Buku panduan PDF (F14)
    |--------------------------------------------------------------------------
    |
    | Perintah "php artisan guide:pdf" merender halaman cetak panduan menjadi PDF
    | dengan Chrome/Chromium headless. Kosongkan untuk mencari otomatis.
    |
    */

    'chrome_path' => env('JUALIN_CHROME_PATH'),

];
