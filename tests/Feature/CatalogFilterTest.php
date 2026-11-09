<?php

use App\Enums\BookingStatus;
use App\Enums\CodArea;
use App\Enums\ProductCondition;
use App\Enums\ReviewDirection;
use App\Models\Booking;
use App\Models\Product;
use App\Models\Review;
use App\Models\SearchLog;
use App\Models\User;

test('catalog filters by condition, price range, and cod area', function () {
    Product::factory()->approved()->create(['name' => 'Cocok Semua', 'condition' => ProductCondition::Good, 'price' => 50_000, 'cod_areas' => [CodArea::Around]]);
    Product::factory()->approved()->create(['name' => 'Kondisi Beda', 'condition' => ProductCondition::Fair, 'price' => 50_000, 'cod_areas' => [CodArea::Around]]);
    Product::factory()->approved()->create(['name' => 'Terlalu Mahal', 'condition' => ProductCondition::Good, 'price' => 150_000, 'cod_areas' => [CodArea::Around]]);
    Product::factory()->approved()->create(['name' => 'Area Beda', 'condition' => ProductCondition::Good, 'price' => 50_000, 'cod_areas' => [CodArea::Inside]]);

    $this->get('/katalog?kondisi[]=good&harga_min=10000&harga_max=100000&area=around')
        ->assertOk()
        ->assertSee('Cocok Semua')
        ->assertDontSee('Kondisi Beda')
        ->assertDontSee('Terlalu Mahal')
        ->assertDontSee('Area Beda');
});

test('catalog sorts by price', function () {
    Product::factory()->approved()->create(['name' => 'Barang Mahal', 'price' => 90_000]);
    Product::factory()->approved()->create(['name' => 'Barang Murah', 'price' => 10_000]);

    $this->get('/katalog?urut=termurah')->assertSeeInOrder(['Barang Murah', 'Barang Mahal']);
    $this->get('/katalog?urut=termahal')->assertSeeInOrder(['Barang Mahal', 'Barang Murah']);
});

test('catalog paginates 24 products per page', function () {
    Product::factory()->approved()->count(25)->create();

    $this->get('/katalog')->assertViewHas('products', fn ($products) => $products->count() === 24 && $products->total() === 25);
});

test('searches are logged for popular searches', function () {
    $this->get('/katalog?q=Kalkulus');
    $this->get('/katalog?q=kalkulus');

    expect(SearchLog::query()->where('term', 'kalkulus')->value('count'))->toBe(2);
});

test('the lapak tab lists active sellers sorted by rating', function () {
    $good = User::factory()->seller()->create(['name' => 'Penjual Bintang Lima']);
    $okay = User::factory()->seller()->create(['name' => 'Penjual Bintang Tiga']);
    $inactive = User::factory()->seller()->inactive()->create(['name' => 'Penjual Nonaktif']);
    $empty = User::factory()->seller()->create(['name' => 'Penjual Tanpa Barang']);

    foreach ([[$good, 5], [$okay, 3], [$inactive, 5]] as [$seller, $rating]) {
        $product = Product::factory()->approved()->for($seller, 'seller')->create();
        $booking = Booking::factory()->for($product)->create(['status' => BookingStatus::Completed]);
        Review::query()->create([
            'booking_id' => $booking->id,
            'reviewer_id' => $booking->buyer_id,
            'reviewee_id' => $seller->id,
            'direction' => ReviewDirection::BuyerToSeller,
            'rating' => $rating,
        ]);
    }

    $this->get('/katalog?tab=lapak')
        ->assertOk()
        ->assertSeeInOrder(['Penjual Bintang Lima', 'Penjual Bintang Tiga'])
        ->assertDontSee('Penjual Nonaktif')
        ->assertDontSee($empty->name);
});

test('the seller storefront lists only that seller\'s visible products', function () {
    $seller = User::factory()->seller()->create();
    Product::factory()->approved()->for($seller, 'seller')->create(['name' => 'Barang Lapak Ini']);
    Product::factory()->for($seller, 'seller')->create(['name' => 'Barang Belum Tayang']);
    Product::factory()->approved()->create(['name' => 'Barang Lapak Lain']);

    $this->get("/lapak/{$seller->id}")
        ->assertOk()
        ->assertSee($seller->name)
        ->assertSee('Barang Lapak Ini')
        ->assertDontSee('Barang Belum Tayang')
        ->assertDontSee('Barang Lapak Lain')
        ->assertDontSee($seller->whatsapp);
});

test('storefronts of inactive accounts and admins are not found', function () {
    $this->get('/lapak/'.User::factory()->inactive()->create()->id)->assertNotFound();
    $this->get('/lapak/'.User::factory()->admin()->create()->id)->assertNotFound();
});
