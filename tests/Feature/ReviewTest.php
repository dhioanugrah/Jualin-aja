<?php

use App\Enums\ReviewDirection;
use App\Enums\UserMode;
use App\Models\Booking;
use App\Models\Review;
use App\Models\User;
use App\Notifications\TransactionUpdate;
use Illuminate\Support\Facades\Notification;

test('both parties of a completed booking can review each other once', function () {
    Notification::fake();
    $booking = Booking::factory()->completed()->create();

    $this->actingAs($booking->buyer)->post("/booking/{$booking->code}/ulasan", [
        'rating' => 5,
        'comment' => 'Barang sesuai foto.',
        'tags' => ['Ramah', 'Barang Sesuai'],
    ])->assertSessionHasNoErrors();

    $this->actingAs($booking->seller)->post("/booking/{$booking->code}/ulasan", [
        'rating' => 4,
        'tags' => ['Tepat Waktu'],
    ])->assertSessionHasNoErrors();

    $buyerReview = Review::query()->where('direction', ReviewDirection::BuyerToSeller)->sole();
    $sellerReview = Review::query()->where('direction', ReviewDirection::SellerToBuyer)->sole();

    expect($buyerReview->reviewee_id)->toBe($booking->seller_id)
        ->and($buyerReview->tags)->toBe(['Ramah', 'Barang Sesuai'])
        ->and($sellerReview->reviewee_id)->toBe($booking->buyer_id)
        ->and($booking->buyer->ratingAs(UserMode::Buyer))->toBe(['average' => 4.0, 'count' => 1]);
    Notification::assertSentTo($booking->seller, TransactionUpdate::class);
    Notification::assertSentTo($booking->buyer, TransactionUpdate::class);

    $this->actingAs($booking->buyer)->post("/booking/{$booking->code}/ulasan", ['rating' => 1])->assertForbidden();
    expect(Review::query()->count())->toBe(2);
});

test('only parties of a completed booking within the review window can review', function () {
    $active = Booking::factory()->accepted()->create();
    $completed = Booking::factory()->completed()->create();

    $this->actingAs($active->buyer)->post("/booking/{$active->code}/ulasan", ['rating' => 5])->assertForbidden();
    $this->actingAs(User::factory()->create())->post("/booking/{$completed->code}/ulasan", ['rating' => 5])->assertForbidden();

    $this->travel(config('jualin.review_window_days') + 1)->days();
    $this->actingAs($completed->buyer)->post("/booking/{$completed->code}/ulasan", ['rating' => 5])->assertForbidden();

    expect(Review::query()->count())->toBe(0);
});

test('ratings must be 1 to 5 and tags must match the review direction', function () {
    $booking = Booking::factory()->completed()->create();

    $this->actingAs($booking->buyer)->post("/booking/{$booking->code}/ulasan", ['rating' => 6])->assertSessionHasErrors('rating');
    $this->actingAs($booking->buyer)->post("/booking/{$booking->code}/ulasan", ['rating' => 5, 'tags' => ['Pembayaran Pas']])->assertSessionHasErrors('tags.0');
});

test('the completed booking page shows the rating form until the user has reviewed', function () {
    $booking = Booking::factory()->completed()->create();

    $this->actingAs($booking->seller)->get("/booking/{$booking->code}")->assertSee('Beri rating untuk pembeli');

    $this->actingAs($booking->seller)->post("/booking/{$booking->code}/ulasan", ['rating' => 5]);

    $this->actingAs($booking->seller)->get("/booking/{$booking->code}")
        ->assertDontSee('Beri rating untuk pembeli')
        ->assertSee('Penjual menilai pembeli');
});
