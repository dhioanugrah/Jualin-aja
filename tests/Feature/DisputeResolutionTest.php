<?php

use App\Enums\BookingStatus;
use App\Enums\ViolationStatus;
use App\Enums\ViolationType;
use App\Models\Booking;
use App\Models\User;
use App\Models\Violation;
use App\Notifications\TransactionUpdate;
use Illuminate\Support\Facades\Notification;

function disputedBooking(): Booking
{
    $booking = Booking::factory()->accepted()->create(['cod_at' => now()->subHours(2)]);
    $booking->forceFill([
        'status' => BookingStatus::Disputed,
        'no_show_reported_by' => $booking->seller_id,
        'no_show_reported_at' => now()->subHour(),
        'dispute_note' => 'Saya datang dan menunggu 30 menit.',
    ])->save();
    $booking->violations()->create(['user_id' => $booking->buyer_id, 'type' => ViolationType::NoShow, 'status' => ViolationStatus::Disputed]);

    return $booking;
}

test('accepting a dispute revokes the no-show violation and lifts a sanction that no longer applies', function () {
    Notification::fake();
    $booking = disputedBooking();
    Violation::query()->create(['user_id' => $booking->buyer_id, 'booking_id' => Booking::factory()->create()->id, 'type' => ViolationType::NoConfirm, 'status' => ViolationStatus::Active]);
    $booking->buyer->forceFill(['booking_blocked_until' => now()->addDays(3)])->save();

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/admin/sengketa/{$booking->code}", ['outcome' => 'accepted'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($booking->refresh()->status)->toBe(BookingStatus::Cancelled)
        ->and($booking->dispute_outcome)->toBe('accepted')
        ->and($booking->violations()->sole()->status)->toBe(ViolationStatus::Revoked)
        ->and($booking->buyer->refresh()->booking_blocked_until)->toBeNull();
    Notification::assertSentTo($booking->seller, TransactionUpdate::class);
    Notification::assertSentTo($booking->buyer, TransactionUpdate::class);
});

test('rejecting a dispute keeps the violation active', function () {
    $booking = disputedBooking();

    $this->actingAs(User::factory()->admin()->create())->patch("/admin/sengketa/{$booking->code}", ['outcome' => 'rejected']);

    expect($booking->refresh()->dispute_outcome)->toBe('rejected')
        ->and($booking->violations()->sole()->status)->toBe(ViolationStatus::Active);
});

test('a dispute can only be decided once and only by admins', function () {
    $booking = disputedBooking();
    $admin = User::factory()->admin()->create();

    $this->actingAs($booking->buyer)->patch("/admin/sengketa/{$booking->code}", ['outcome' => 'accepted'])->assertForbidden();
    $this->actingAs($admin)->patch("/admin/sengketa/{$booking->code}", ['outcome' => 'rejected']);
    $this->actingAs($admin)->patch("/admin/sengketa/{$booking->code}", ['outcome' => 'accepted'])->assertStatus(422);

    expect($booking->violations()->sole()->status)->toBe(ViolationStatus::Active);
});

test('admins can revoke a single violation and toggle an account', function () {
    $admin = User::factory()->admin()->create();
    $violation = Violation::query()->create(['user_id' => User::factory()->create()->id, 'booking_id' => Booking::factory()->create()->id, 'type' => ViolationType::LateCancel, 'status' => ViolationStatus::Active]);

    $this->actingAs($admin)->patch("/admin/pelanggaran/{$violation->id}/cabut");
    $this->actingAs($admin)->patch("/admin/pengguna/{$violation->user_id}/status");

    expect($violation->refresh()->status)->toBe(ViolationStatus::Revoked)
        ->and($violation->user->refresh()->is_active)->toBeFalse();

    $this->actingAs($admin)->get('/admin/sengketa')->assertOk()->assertSee($violation->user->name);
});
