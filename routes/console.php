<?php

use App\Models\Promotion;
use App\Services\BookingService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('bookings:expire', function (BookingService $bookings): void {
    $result = $bookings->expireOverdue();
    $this->info("Booking kedaluwarsa: {$result['bookings']}, batal karena tidak konfirmasi: {$result['unconfirmed']}, penawaran kedaluwarsa: {$result['offers']}.");
})->purpose('Tutup booking yang tidak direspons, belum dikonfirmasi, dan penawaran yang lewat batas');

Artisan::command('bookings:remind', function (BookingService $bookings): void {
    $this->info('Pengingat konfirmasi terkirim: '.$bookings->sendReminders().'.');
})->purpose('Kirim pengingat konfirmasi kehadiran H-3 jam sebelum COD');

Artisan::command('promotions:expire', function (): void {
    // Promosi barang yang sudah tidak tayang (terjual, disembunyikan, atau dihapus) diakhiri supaya slot Unggulan terisi barang lain.
    $ended = Promotion::query()
        ->running()
        ->whereDoesntHave('product', fn ($query) => $query->visible())
        ->update(['ends_at' => now()]);

    $this->info("Promosi diakhiri: {$ended}.");
})->purpose('Akhiri promosi untuk barang yang sudah tidak tayang');

/*
 * Proposal 3.6 menyebut "tiap jam". Dijalankan tiap 15 menit supaya batas H-1 jam
 * konfirmasi kehadiran tidak meleset sampai mendekati jadwal COD.
 */
Schedule::command('bookings:remind')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('bookings:expire')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('promotions:expire')->daily();
