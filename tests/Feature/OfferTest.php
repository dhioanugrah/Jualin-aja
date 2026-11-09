<?php

use App\Enums\OfferStatus;
use App\Models\Offer;
use App\Models\Product;
use App\Models\User;
use App\Notifications\TransactionUpdate;
use Illuminate\Support\Facades\Notification;

test('buyers can make an offer below the listed price and the seller is notified', function () {
    Notification::fake();
    $product = Product::factory()->approved()->create(['price' => 50_000]);
    $buyer = User::factory()->buyer()->create();

    $this->actingAs($buyer)->post("/barang/{$product->id}/tawar", ['price' => 45_000, 'message' => 'Bisa kurang?'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $offer = Offer::query()->sole();
    expect($offer->buyer_id)->toBe($buyer->id)
        ->and($offer->price)->toBe(45_000)
        ->and($offer->status)->toBe(OfferStatus::Pending);
    Notification::assertSentTo($product->seller, TransactionUpdate::class);
});

test('an offer must be below the listed price', function () {
    $product = Product::factory()->approved()->create(['price' => 50_000]);

    $this->actingAs(User::factory()->create())->post("/barang/{$product->id}/tawar", ['price' => 50_000])
        ->assertSessionHasErrors('price');
});

test('a buyer can only have one active offer per product', function () {
    $offer = Offer::factory()->create();

    $this->actingAs($offer->buyer)->post("/barang/{$offer->product_id}/tawar", ['price' => 1_000])
        ->assertSessionHasErrors('price');

    expect(Offer::query()->count())->toBe(1);
});

test('donations and own products cannot be offered on', function () {
    $donation = Product::factory()->approved()->create(['is_donation' => true]);
    $product = Product::factory()->approved()->create(['price' => 50_000]);

    $this->actingAs(User::factory()->create())->post("/barang/{$donation->id}/tawar", ['price' => 1_000])->assertForbidden();
    $this->actingAs($product->seller)->post("/barang/{$product->id}/tawar", ['price' => 10_000])->assertForbidden();
});

test('the seller can accept an offer, giving the buyer 24 hours to book', function () {
    $this->freezeSecond();
    Notification::fake();
    $offer = Offer::factory()->create();

    $this->actingAs($offer->product->seller)->patch("/penawaran/{$offer->id}/terima")->assertRedirect();

    $offer->refresh();
    expect($offer->status)->toBe(OfferStatus::Accepted)
        ->and($offer->expires_at->equalTo(now()->addHours(24)))->toBeTrue();
    Notification::assertSentTo($offer->buyer, TransactionUpdate::class);
});

test('only the product owner can respond to an offer', function () {
    $offer = Offer::factory()->create();

    $this->actingAs($offer->buyer)->patch("/penawaran/{$offer->id}/terima")->assertForbidden();
    $this->actingAs(User::factory()->create())->patch("/penawaran/{$offer->id}/tolak")->assertForbidden();
});

test('the buyer can cancel their own offer', function () {
    $offer = Offer::factory()->create();

    $this->actingAs($offer->buyer)->patch("/penawaran/{$offer->id}/batal")->assertRedirect();

    expect($offer->refresh()->status)->toBe(OfferStatus::Cancelled);
});
