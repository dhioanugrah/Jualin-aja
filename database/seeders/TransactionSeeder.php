<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\CodArea;
use App\Enums\OfferStatus;
use App\Enums\ProductStatus;
use App\Models\Booking;
use App\Models\CodLocation;
use App\Models\Offer;
use App\Models\SearchLog;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Data demo Sprint 2: penawaran, booking dalam berbagai status, dan pencarian populer.
 */
class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $intan = User::query()->where('nim', '11241031')->firstOrFail();
        $fajar = User::query()->where('nim', '11241045')->firstOrFail();
        $aulia = User::query()->where('nim', '11221001')->firstOrFail();
        $bagas = User::query()->where('nim', '11211014')->firstOrFail();
        $inside = CodLocation::query()->where('area', CodArea::Inside)->orderBy('id')->get();

        $auliaProducts = $aulia->products()->where('status', ProductStatus::Approved)->orderBy('id')->get();
        $bagasProducts = $bagas->products()->where('status', ProductStatus::Approved)->orderBy('id')->get();

        // Transaksi selesai: membangun skor keandalan Fajar dan Aulia.
        foreach ($auliaProducts->take(2) as $index => $product) {
            $booking = Booking::query()->create([
                'product_id' => $product->id,
                'buyer_id' => $fajar->id,
                'seller_id' => $aulia->id,
                'agreed_price' => $product->price,
                'cod_at' => now()->subDays(5 - $index)->setTime(10, 0),
                'cod_area' => CodArea::Inside,
                'cod_location_id' => $inside[$index]->id,
                'status' => BookingStatus::Completed,
                'respond_by' => now()->subDays(6),
            ]);
            $booking->forceFill(['accepted_at' => now()->subDays(6), 'completed_at' => $booking->cod_at, 'completed_by' => $aulia->id])->save();
            $product->forceFill(['status' => ProductStatus::Sold, 'sold_at' => $booking->cod_at])->save();
        }

        // Booking menunggu respons Aulia dari Intan.
        Booking::query()->create([
            'product_id' => $auliaProducts[2]->id,
            'buyer_id' => $intan->id,
            'seller_id' => $aulia->id,
            'agreed_price' => $auliaProducts[2]->price,
            'cod_at' => now()->addDay()->setTime(13, 0),
            'cod_area' => CodArea::Inside,
            'cod_location_id' => $inside[1]->id,
            'note' => 'Saya pakai jaket almamater, tunggu di depan pintu.',
            'status' => BookingStatus::Pending,
            'respond_by' => now()->addHours(12),
        ]);

        // Booking diterima untuk besok antara Fajar dan Bagas.
        $accepted = Booking::query()->create([
            'product_id' => $bagasProducts[0]->id,
            'buyer_id' => $fajar->id,
            'seller_id' => $bagas->id,
            'agreed_price' => $bagasProducts[0]->price,
            'cod_at' => now()->addDays(2)->setTime(10, 0),
            'cod_area' => CodArea::Inside,
            'cod_location_id' => $inside[0]->id,
            'status' => BookingStatus::Accepted,
            'respond_by' => now()->addHours(12),
        ]);
        $accepted->forceFill(['accepted_at' => now()])->save();

        // Penawaran: satu menunggu, satu diterima dan siap dijadikan booking.
        Offer::query()->create([
            'product_id' => $auliaProducts[3]->id,
            'buyer_id' => $fajar->id,
            'price' => (int) round($auliaProducts[3]->price * 0.85, -3),
            'message' => 'Kak, boleh kurang sedikit? Bisa COD besok.',
            'status' => OfferStatus::Pending,
        ]);

        Offer::query()->create([
            'product_id' => $bagasProducts[1]->id,
            'buyer_id' => $intan->id,
            'price' => (int) round($bagasProducts[1]->price * 0.9, -3),
            'status' => OfferStatus::Accepted,
            'expires_at' => now()->addHours(20),
        ]);

        foreach (['kalkulus' => 14, 'kipas angin' => 9, 'jas lab' => 7, 'rice cooker' => 5, 'meja lipat' => 4] as $term => $count) {
            SearchLog::query()->updateOrCreate(['term' => $term], ['count' => $count]);
        }

        $intan->favorites()->syncWithoutDetaching($auliaProducts->slice(4, 2)->pluck('id'));
    }
}
