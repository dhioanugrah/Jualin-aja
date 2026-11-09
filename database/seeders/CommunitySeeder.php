<?php

namespace Database\Seeders;

use App\Enums\AdPlacement;
use App\Enums\BookingStatus;
use App\Enums\CodArea;
use App\Enums\ProductStatus;
use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Enums\ReviewDirection;
use App\Enums\ViolationStatus;
use App\Enums\ViolationType;
use App\Models\Ad;
use App\Models\Booking;
use App\Models\CodLocation;
use App\Models\Feedback;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Data demo Sprint 3: rating dua arah, promosi, banner iklan, laporan, sengketa, dan masukan.
 */
class CommunitySeeder extends Seeder
{
    public function run(): void
    {
        $intan = User::query()->where('nim', '11241031')->firstOrFail();
        $fajar = User::query()->where('nim', '11241045')->firstOrFail();
        $aulia = User::query()->where('nim', '11221001')->firstOrFail();
        $bagas = User::query()->where('nim', '11211014')->firstOrFail();

        $this->seedReviews($fajar, $aulia);
        $this->seedPromotions($aulia, $bagas);
        $this->seedAds();
        $this->seedReportAndDispute($intan, $bagas);

        foreach ([
            [$intan->id, 5, 'Cari barang per lapak enak banget, lebih cepat daripada scroll grup WA.', 'katalog'],
            [$fajar->id, 4, 'Kode serah terima bikin tenang. Konfirmasi H-3 jam kadang lupa, semoga ada pengingat WA.', 'booking/x'],
            [null, 3, 'Tombol Jual di HP agak ketutup jari, tapi overall oke.', ''],
            [$aulia->id, 5, 'Fitur promosi gratis membantu barang cepat laku.', 'barang-saya'],
        ] as [$userId, $score, $comment, $page]) {
            Feedback::query()->create(['user_id' => $userId, 'score' => $score, 'comment' => $comment, 'page' => '/'.$page]);
        }
    }

    private function seedReviews(User $buyer, User $seller): void
    {
        $bookings = Booking::query()
            ->where('status', BookingStatus::Completed)
            ->where('buyer_id', $buyer->id)
            ->where('seller_id', $seller->id)
            ->orderBy('cod_at')
            ->get();

        $first = $bookings->first();

        if ($first === null) {
            return;
        }

        $first->reviews()->create([
            'reviewer_id' => $buyer->id,
            'reviewee_id' => $seller->id,
            'direction' => ReviewDirection::BuyerToSeller,
            'rating' => 5,
            'comment' => 'Barang sesuai foto, kak Aulia datang tepat waktu di lobi GKB. Recommended!',
            'tags' => ['Barang Sesuai', 'Tepat Waktu', 'Ramah'],
        ]);
        $first->reviews()->create([
            'reviewer_id' => $seller->id,
            'reviewee_id' => $buyer->id,
            'direction' => ReviewDirection::SellerToBuyer,
            'rating' => 5,
            'comment' => 'Pembeli datang lebih awal dan langsung cek barang.',
            'tags' => ['Tepat Waktu', 'Pembayaran Pas'],
        ]);

        // Booking kedua: pembeli sudah menilai, penjual belum (untuk demo form rating penjual).
        $bookings->skip(1)->first()?->reviews()->create([
            'reviewer_id' => $buyer->id,
            'reviewee_id' => $seller->id,
            'direction' => ReviewDirection::BuyerToSeller,
            'rating' => 4,
            'comment' => 'Responsif di WA, harga pas untuk kondisi segini.',
            'tags' => ['Responsif', 'Harga Pas'],
        ]);
    }

    private function seedPromotions(User ...$sellers): void
    {
        foreach ($sellers as $seller) {
            $product = $seller->products()->where('status', ProductStatus::Approved)->latest('id')->first();

            $product?->promotions()->create([
                'user_id' => $seller->id,
                'starts_at' => now()->subHours(6),
                'ends_at' => now()->subHours(6)->addDays((int) config('jualin.promotion_days')),
            ]);
        }
    }

    private function seedAds(): void
    {
        Storage::disk('public')->deleteDirectory('ads/seed');

        foreach ([
            ['Open Recruitment HMIF 2026', 'Himpunan Mahasiswa Informatika', '#1E5AA8', '#F5B700', AdPlacement::HomeTop],
            ['Bazar Buku Murah UKM Literasi', 'UKM Literasi ITK', '#0F172A', '#38BDF8', AdPlacement::HomeTop],
            ['Fotokopi & Jilid Kilat Karang Joang', 'Usaha mahasiswa', '#047857', '#FDE68A', AdPlacement::CatalogInline],
        ] as $index => [$title, $advertiser, $background, $accent, $placement]) {
            $path = "ads/seed/iklan-{$index}.svg";
            Storage::disk('public')->put($path, <<<SVG
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1600 500">
                  <rect width="1600" height="500" fill="{$background}"/>
                  <circle cx="1450" cy="60" r="260" fill="{$accent}"/>
                  <text x="90" y="230" font-family="Plus Jakarta Sans, sans-serif" font-size="78" font-weight="800" fill="#fff">{$title}</text>
                  <text x="90" y="310" font-family="Plus Jakarta Sans, sans-serif" font-size="40" fill="#fff" opacity=".8">{$advertiser}</text>
                </svg>
                SVG);

            Ad::query()->create([
                'title' => $title,
                'advertiser' => $advertiser,
                'image' => $path,
                'url' => 'https://itk.ac.id',
                'placement' => $placement,
                'starts_at' => now()->subDays(2),
                'ends_at' => now()->addDays(12),
                'is_active' => true,
            ])->forceFill(['impressions' => 140 - $index * 30, 'clicks' => 9 - $index * 2])->save();
        }
    }

    private function seedReportAndDispute(User $buyer, User $seller): void
    {
        $product = Product::query()->visible()->whereKeyNot($seller->products()->pluck('id'))->latest('id')->first();

        $product?->reports()->create([
            'reporter_id' => $buyer->id,
            'reason' => ReportReason::FakeInfo,
            'note' => 'Fotonya sepertinya diambil dari internet, bukan barang asli.',
            'status' => ReportStatus::Open,
        ]);

        // Sengketa: Bagas melaporkan Intan tidak datang, Intan membantah.
        $sellerProduct = $seller->products()->where('status', ProductStatus::Approved)->oldest('id')->skip(1)->first();

        if ($sellerProduct === null) {
            return;
        }

        $codAt = now()->subDay()->setTime(15, 0);
        $booking = Booking::query()->create([
            'product_id' => $sellerProduct->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'agreed_price' => $sellerProduct->price,
            'cod_at' => $codAt,
            'cod_area' => CodArea::Inside,
            'cod_location_id' => CodLocation::query()->where('area', CodArea::Inside)->value('id'),
            'status' => BookingStatus::Disputed,
            'respond_by' => $codAt->copy()->subDay(),
        ]);
        $booking->forceFill([
            'accepted_at' => $codAt->copy()->subDay(),
            'buyer_confirmed_at' => $codAt->copy()->subHours(2),
            'seller_confirmed_at' => $codAt->copy()->subHours(2),
            'no_show_reported_by' => $seller->id,
            'no_show_reported_at' => $codAt->copy()->addHour(),
            'dispute_note' => 'Saya datang 14.55 dan menunggu di lobi perpustakaan sampai 15.30, sudah chat WA tapi tidak dibalas.',
        ])->save();

        $buyer->violations()->create([
            'booking_id' => $booking->id,
            'type' => ViolationType::NoShow,
            'status' => ViolationStatus::Disputed,
            'note' => "Dilaporkan oleh {$seller->name}.",
        ]);
    }
}
