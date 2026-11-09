<?php

use App\Enums\BookingStatus;
use App\Enums\UserMode;
use App\Enums\ViolationType;
use App\Models\Booking;
use App\Models\User;
use App\Notifications\TransactionUpdate;
use App\Services\ViolationRecorder;
use Illuminate\Support\Facades\Notification;

test('sanctions escalate with the number of violations in 30 days', function () {
    Notification::fake();
    $this->freezeSecond();
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $recorder = app(ViolationRecorder::class);

    $recorder->record($user, Booking::factory()->create(), ViolationType::NoConfirm);
    expect($user->refresh()->booking_blocked_until)->toBeNull();

    $recorder->record($user, Booking::factory()->create(), ViolationType::NoShow);
    expect($user->refresh()->booking_blocked_until->equalTo(now()->addDays(3)))->toBeTrue();
    Notification::assertNothingSentTo($admin);

    $recorder->record($user, Booking::factory()->create(), ViolationType::LateCancel);
    expect($user->refresh()->booking_blocked_until->equalTo(now()->addDays(14)))->toBeTrue();
    Notification::assertSentTo($admin, TransactionUpdate::class);
});

test('violations older than 30 days do not count toward sanctions', function () {
    $user = User::factory()->create();
    $recorder = app(ViolationRecorder::class);

    $recorder->record($user, Booking::factory()->create(), ViolationType::NoConfirm);
    $this->travel(31)->days();
    $recorder->record($user, Booking::factory()->create(), ViolationType::NoConfirm);

    expect($user->refresh()->booking_blocked_until)->toBeNull();
});

test('reliability counts completed bookings against the user\'s own violations', function () {
    $buyer = User::factory()->create();
    expect($buyer->reliability(UserMode::Buyer))->toBeNull();

    Booking::factory()->count(3)->completed()->for($buyer, 'buyer')->create();
    $failed = Booking::factory()->for($buyer, 'buyer')->create(['status' => BookingStatus::Cancelled]);
    app(ViolationRecorder::class)->record($buyer, $failed, ViolationType::NoShow);

    // Booking yang gagal karena penjual tidak menurunkan skor pembeli.
    $sellerFault = Booking::factory()->for($buyer, 'buyer')->create(['status' => BookingStatus::Cancelled]);
    app(ViolationRecorder::class)->record($sellerFault->seller, $sellerFault, ViolationType::NoShow);

    expect($buyer->reliability(UserMode::Buyer))->toBe(['percent' => 75, 'total' => 4]);
});
