<?php

use App\Enums\BookingStatus;
use App\Enums\OfferStatus;
use App\Enums\ViolationType;
use App\Models\Booking;
use App\Models\Offer;
use App\Notifications\TransactionUpdate;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 11, 10)->setTime(8, 0));
});

test('pending bookings past their response deadline expire', function () {
    $overdue = Booking::factory()->create(['respond_by' => now()->subMinute()]);
    $fresh = Booking::factory()->create(['respond_by' => now()->addHour()]);

    $this->artisan('bookings:expire')->assertSuccessful();

    expect($overdue->refresh()->status)->toBe(BookingStatus::Expired)
        ->and($fresh->refresh()->status)->toBe(BookingStatus::Pending);
});

test('queued bookings behind an accepted booking do not expire', function () {
    $accepted = Booking::factory()->accepted()->create();
    $queued = Booking::factory()->for($accepted->product)->create(['respond_by' => now()->subMinute()]);

    $this->artisan('bookings:expire');

    expect($queued->refresh()->status)->toBe(BookingStatus::Pending);
});

test('bookings not confirmed by one hour before cod are cancelled with a violation for the missing party', function () {
    $booking = Booking::factory()->accepted()->scheduledIn(50)->create();
    $booking->forceFill(['seller_confirmed_at' => now()])->save();

    $this->artisan('bookings:expire');

    expect($booking->refresh()->status)->toBe(BookingStatus::Cancelled)
        ->and($booking->buyer->violations()->sole()->type)->toBe(ViolationType::NoConfirm)
        ->and($booking->seller->violations()->count())->toBe(0);
});

test('fully confirmed bookings are left alone', function () {
    $booking = Booking::factory()->accepted()->scheduledIn(50)->create();
    $booking->forceFill(['seller_confirmed_at' => now(), 'buyer_confirmed_at' => now()])->save();

    $this->artisan('bookings:expire');

    expect($booking->refresh()->status)->toBe(BookingStatus::Accepted);
});

test('accepted offers that were not booked within 24 hours expire', function () {
    $stale = Offer::factory()->accepted()->create(['expires_at' => now()->subMinute()]);
    $fresh = Offer::factory()->accepted()->create();

    $this->artisan('bookings:expire');

    expect($stale->refresh()->status)->toBe(OfferStatus::Expired)
        ->and($fresh->refresh()->status)->toBe(OfferStatus::Accepted);
});

test('attendance reminders are sent once when the confirmation window opens', function () {
    Notification::fake();
    $soon = Booking::factory()->accepted()->scheduledIn(150)->create();
    $later = Booking::factory()->accepted()->scheduledIn(6 * 60)->create();

    $this->artisan('bookings:remind')->assertSuccessful();
    $this->artisan('bookings:remind');

    Notification::assertSentToTimes($soon->buyer, TransactionUpdate::class, 1);
    Notification::assertSentToTimes($soon->seller, TransactionUpdate::class, 1);
    Notification::assertNothingSentTo($later->buyer);
    expect($soon->refresh()->reminded_at)->not->toBeNull();
});
